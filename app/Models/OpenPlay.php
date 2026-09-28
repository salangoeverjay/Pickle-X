<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * An open play session and, while live, its court board.
 *
 * board = [
 *   'seq'    => int,  // last queue entry id handed out
 *   'queue'  => [['id' => int, 'ids' => [participantId, ...], 'locked' => bool], ...],
 *   'courts' => [['n' => 1, 'a' => ['ids' => [..], 'locked' => bool]|null, 'b' => ..., 'started_at' => unix|null, 'wins' => int], ...],
 * ]
 *
 * A queue entry is one waiting player, or two locked partners who always play together.
 * ponytail: the board is one JSON column guarded by a row lock; fine for one organizer
 * per session, move queue/courts to tables if many writers ever edit one board at once.
 */
class OpenPlay extends Model
{
    protected $fillable = [
        'organizer_id', 'title', 'club', 'venue', 'starts_at', 'ends_at', 'courts', 'cap',
        'race_to', 'rotation', 'visibility', 'fee', 'status', 'auto_fill', 'board', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'auto_fill' => 'boolean',
            'board' => 'array',
        ];
    }

    private ?Collection $participantCache = null;

    // ---------------------------------------------------------------- relations

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopePublic(Builder $query): void
    {
        $query->where('visibility', 'public');
    }

    // ---------------------------------------------------------------- display

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function rotationLabel(): string
    {
        return $this->rotation === 'winners' ? 'Winners stay (2 max)' : 'Everyone rotates';
    }

    public function feeLabel(): string
    {
        return $this->fee ?: 'Free';
    }

    /** "Today" or "Sat, Oct 3". */
    public function whenLabel(): string
    {
        return $this->starts_at->isToday() ? 'Today' : $this->starts_at->format('D, M j');
    }

    /** "6:00 – 10:00 PM", or "11:00 AM – 1:00 PM" across noon. */
    public function timeRange(): string
    {
        $sameHalf = $this->starts_at->format('A') === $this->ends_at->format('A');

        return $this->starts_at->format($sameHalf ? 'g:i' : 'g:i A').' – '.$this->ends_at->format('g:i A');
    }

    /** Joined players still in the session. Uses withCount('activeParticipants') when loaded. */
    public function playerCount(): int
    {
        return $this->active_participants_count ?? $this->activeParticipants()->count();
    }

    public function spotsLeft(): int
    {
        return max(0, $this->cap - $this->playerCount());
    }

    // ---------------------------------------------------------------- board reads

    /** Participants keyed by id, loaded once per request. */
    public function participantMap(): Collection
    {
        return $this->participantCache ??= $this->participants()->with('user')->get()->keyBy('id');
    }

    public function participant(int $id): ?Participant
    {
        return $this->participantMap()->get($id);
    }

    /** First names joined with "&": "Ben & Mika". */
    public function names(array $ids): string
    {
        return collect($ids)->map(fn ($id) => $this->participant($id)?->firstName() ?? 'Guest')->join(' & ');
    }

    public function queue(): array
    {
        return $this->board['queue'] ?? [];
    }

    public function boardCourts(): array
    {
        return $this->board['courts'] ?? [];
    }

    public function queuedPlayerCount(): int
    {
        return collect($this->queue())->sum(fn ($e) => count($e['ids']));
    }

    public function busyCourtCount(): int
    {
        return collect($this->boardCourts())->filter(fn ($c) => $c['a'])->count();
    }

    public function nextUpTeams(): ?array
    {
        return self::nextUp($this->queue());
    }

    /** Index of the court that has been playing longest (likely next to open). */
    public function longestCourtIndex(): ?int
    {
        $busy = collect($this->boardCourts())->filter(fn ($c) => $c['a']);

        return $busy->isEmpty() ? null : $busy->sortBy('started_at')->keys()->first();
    }

    /** [participantId => games played in this session]. */
    public function gamesTonight(): array
    {
        return GamePlayer::query()
            ->whereIn('game_id', $this->games()->select('id'))
            ->selectRaw('participant_id, count(*) as n')
            ->groupBy('participant_id')
            ->pluck('n', 'participant_id')
            ->all();
    }

    /**
     * Where a member stands in this live session, or null if they are not in it.
     *
     * ['state' => 'court', 'court' => n, 'index' => i, 'partner' => ?, 'opponents' => ..., 'started_at' => unix]
     * ['state' => 'next', 'partner' => ?, 'opponents' => ...]
     * ['state' => 'queued', 'position' => n, 'ahead' => n]
     */
    public function statusFor(User $user): ?array
    {
        if (! $this->isLive()) {
            return null;
        }

        $me = $this->participantMap()->first(fn ($p) => $p->user_id === $user->id && ! $p->left_at);
        if (! $me) {
            return null;
        }

        foreach ($this->boardCourts() as $i => $court) {
            if (! $court['a']) {
                continue;
            }
            $mine = in_array($me->id, $court['a']['ids']) ? $court['a'] : (in_array($me->id, $court['b']['ids']) ? $court['b'] : null);
            if ($mine) {
                $opp = $mine === $court['a'] ? $court['b'] : $court['a'];

                return [
                    'state' => 'court', 'court' => $court['n'], 'index' => $i, 'started_at' => $court['started_at'],
                    'partner' => $this->names(array_diff($mine['ids'], [$me->id])) ?: null,
                    'opponents' => $this->names($opp['ids']),
                ];
            }
        }

        $ahead = 0;
        $next = $this->nextUpTeams();
        foreach ($this->queue() as $i => $entry) {
            if (in_array($me->id, $entry['ids'])) {
                if ($next && in_array($entry['id'], $next['used'])) {
                    $mine = in_array($me->id, $next['a']['ids']) ? $next['a'] : $next['b'];
                    $opp = $mine === $next['a'] ? $next['b'] : $next['a'];

                    return [
                        'state' => 'next',
                        'partner' => $this->names(array_diff($mine['ids'], [$me->id])) ?: null,
                        'opponents' => $this->names($opp['ids']),
                    ];
                }

                return ['state' => 'queued', 'position' => $i + 1, 'ahead' => $ahead];
            }
            $ahead += count($entry['ids']);
        }

        return null;
    }

    // ---------------------------------------------------------------- queue rules

    /**
     * The next two teams from the front of the queue. Locked pairs are a team;
     * single players pair up in queue order.
     *
     * @return array{a: array, b: array, used: int[]}|null
     */
    public static function nextUp(array $queue): ?array
    {
        $teams = [];
        $used = [];
        $single = null;

        foreach ($queue as $entry) {
            if (count($teams) === 2) {
                break;
            }
            if ($entry['locked'] && count($entry['ids']) === 2) {
                $teams[] = ['ids' => $entry['ids'], 'locked' => true];
                $used[] = $entry['id'];
            } elseif ($single) {
                $teams[] = ['ids' => [$single['ids'][0], $entry['ids'][0]], 'locked' => false];
                array_push($used, $single['id'], $entry['id']);
                $single = null;
            } else {
                $single = $entry;
            }
        }

        return count($teams) === 2 ? ['a' => $teams[0], 'b' => $teams[1], 'used' => $used] : null;
    }

    /** The next single team (for winners-stay challengers). */
    public static function nextTeam(array $queue): ?array
    {
        $single = null;

        foreach ($queue as $entry) {
            if ($entry['locked'] && count($entry['ids']) === 2) {
                if (! $single) {
                    return ['team' => ['ids' => $entry['ids'], 'locked' => true], 'used' => [$entry['id']]];
                }

                continue;
            }
            if ($single) {
                return ['team' => ['ids' => [$single['ids'][0], $entry['ids'][0]], 'locked' => false], 'used' => [$single['id'], $entry['id']]];
            }
            $single = $entry;
        }

        return null;
    }

    /** Race format: exactly one team reaches the target. */
    public static function isValidScore(int $a, int $b, int $to): bool
    {
        return $a >= 0 && $b >= 0 && $a <= $to && $b <= $to && ($a === $to) !== ($b === $to);
    }

    // ---------------------------------------------------------------- board writes

    /** Start the session: open the courts, queue everyone who joined, fill courts. */
    public function start(): void
    {
        if ($this->status !== 'upcoming') {
            throw new InvalidArgumentException('Only upcoming open plays can start.');
        }

        $this->mutate(function (array &$board, self $play) {
            $board = ['seq' => 0, 'queue' => [], 'courts' => []];
            foreach (range(1, max(1, $play->courts)) as $n) {
                $board['courts'][] = ['n' => $n, 'a' => null, 'b' => null, 'started_at' => null, 'wins' => 0];
            }
            foreach ($play->activeParticipants()->orderBy('id')->pluck('id') as $id) {
                $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$id], 'locked' => false];
            }
            $play->forceFill(['status' => 'live', 'started_at' => now(), 'auto_fill' => true]);
        });
    }

    public function end(): void
    {
        $this->update(['status' => 'done', 'ended_at' => now()]);
    }

    public function setAutoFill(bool $on): void
    {
        $this->mutate(fn (array &$board, self $play) => $play->auto_fill = $on);
    }

    /** Put the next two teams on court $n. Returns the teams, or null if fewer than 4 are waiting. */
    public function fillCourt(int $n): ?array
    {
        return $this->mutate(fn (array &$board) => self::seatNextUp($board, $n));
    }

    /** Log a result on court $n and send players back to the queue per the rotation rule. */
    public function recordScore(int $n, int $scoreA, int $scoreB): Game
    {
        if (! self::isValidScore($scoreA, $scoreB, $this->race_to)) {
            throw new InvalidArgumentException("Race to {$this->race_to}: exactly one team must reach it.");
        }

        return $this->mutate(function (array &$board, self $play) use ($n, $scoreA, $scoreB) {
            $i = collect($board['courts'])->search(fn ($c) => $c['n'] === $n);
            $court = $i === false ? null : $board['courts'][$i];
            if (! $court || ! $court['a']) {
                throw new InvalidArgumentException("Court {$n} has no game in progress.");
            }

            $side = $scoreA === $play->race_to ? 'a' : 'b';
            $win = $court[$side];
            $lose = $court[$side === 'a' ? 'b' : 'a'];

            $game = $play->games()->create([
                'court' => $n, 'score_a' => $scoreA, 'score_b' => $scoreB, 'winner' => $side,
                'started_at' => $court['started_at'] ? now()->setTimestamp($court['started_at']) : now(), 'played_at' => now(),
            ]);
            $users = $play->participants()->pluck('user_id', 'id');
            foreach (['a', 'b'] as $team) {
                foreach ($court[$team]['ids'] as $pid) {
                    $game->players()->create([
                        'participant_id' => $pid, 'user_id' => $users[$pid] ?? null, 'team' => $team,
                        'won' => $team === $side, 'locked' => $court[$team]['locked'],
                    ]);
                }
            }

            $active = $play->activeParticipants()->pluck('id')->all();
            $requeue = function (array $team) use (&$board, $active) {
                $ids = array_values(array_intersect($team['ids'], $active));
                if ($team['locked'] && count($ids) === 2) {
                    $board['queue'][] = ['id' => ++$board['seq'], 'ids' => $ids, 'locked' => true];
                } else {
                    foreach ($ids as $id) {
                        $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$id], 'locked' => false];
                    }
                }
            };

            $court = ['n' => $n, 'a' => null, 'b' => null, 'started_at' => null, 'wins' => 0];
            $wins = ($side === 'a' ? $board['courts'][$i]['wins'] : 0) + 1;

            if ($play->rotation === 'winners' && $wins < 2) {
                // Winners stay on (as team A) for up to two straight games.
                $requeue($lose);
                if ($challenger = self::nextTeam($board['queue'])) {
                    $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => ! in_array($e['id'], $challenger['used'])));
                    $court = ['n' => $n, 'a' => $win, 'b' => $challenger['team'], 'started_at' => now()->timestamp, 'wins' => $wins];
                } else {
                    $requeue($win);
                }
            } else {
                $requeue($win);
                $requeue($lose);
            }

            $board['courts'][$i] = $court;

            return $game;
        });
    }

    /** Merge two single queue entries into locked partners (at the earlier spot). */
    public function lockPartners(int $first, int $second): void
    {
        $this->mutate(function (array &$board) use ($first, $second) {
            $idx = collect($board['queue'])->filter(fn ($e) => in_array($e['id'], [$first, $second]) && count($e['ids']) === 1)->keys()->sort()->values();
            if ($idx->count() !== 2) {
                throw new InvalidArgumentException('Pick two single players in the queue.');
            }
            [$i, $j] = $idx->all();
            $board['queue'][$i] = ['id' => ++$board['seq'], 'ids' => [$board['queue'][$i]['ids'][0], $board['queue'][$j]['ids'][0]], 'locked' => true];
            unset($board['queue'][$j]);
            $board['queue'] = array_values($board['queue']);
        });
    }

    /** Split locked partners back into two singles, keeping their place. */
    public function unlockEntry(int $entryId): void
    {
        $this->mutate(function (array &$board) use ($entryId) {
            $queue = [];
            foreach ($board['queue'] as $e) {
                if ($e['id'] === $entryId) {
                    foreach ($e['ids'] as $id) {
                        $queue[] = ['id' => ++$board['seq'], 'ids' => [$id], 'locked' => false];
                    }
                } else {
                    $queue[] = $e;
                }
            }
            $board['queue'] = $queue;
        });
    }

    /** Organizer removes an entry: those players leave the session. */
    public function removeEntry(int $entryId): void
    {
        $this->mutate(function (array &$board, self $play) use ($entryId) {
            $entry = collect($board['queue'])->firstWhere('id', $entryId);
            if ($entry) {
                $play->participants()->whereIn('id', $entry['ids'])->update(['left_at' => now()]);
                $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => $e['id'] !== $entryId));
            }
        });
    }

    public function addWalkin(string $name): Participant
    {
        return $this->mutate(function (array &$board, self $play) use ($name) {
            $p = $play->participants()->create(['name' => $name]);
            $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$p->id], 'locked' => false];

            return $p;
        });
    }

    /** A member joins (or rejoins). Live sessions put them at the back of the queue. */
    public function join(User $user): Participant
    {
        return $this->mutate(function (array &$board, self $play) use ($user) {
            $p = $play->participants()->firstOrNew(['user_id' => $user->id]);
            if ($p->exists && ! $p->left_at) {
                return $p;
            }
            if ($play->status === 'done') {
                throw new InvalidArgumentException('This open play has ended.');
            }
            if ($play->activeParticipants()->count() >= $play->cap) {
                throw new InvalidArgumentException('This open play is full.');
            }
            $p->fill(['name' => $user->name, 'left_at' => null])->save();
            if ($play->isLive()) {
                $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$p->id], 'locked' => false];
            }

            return $p;
        });
    }

    /** A member leaves. They drop out of the queue; if on court they finish the game first. */
    public function leave(User $user): void
    {
        $this->mutate(function (array &$board, self $play) use ($user) {
            $p = $play->participants()->where('user_id', $user->id)->whereNull('left_at')->first();
            if (! $p) {
                return;
            }
            $p->update(['left_at' => now()]);
            $queue = [];
            foreach ($board['queue'] ?? [] as $e) {
                if (! in_array($p->id, $e['ids'])) {
                    $queue[] = $e;
                } elseif (count($e['ids']) === 2) {
                    $queue[] = ['id' => ++$board['seq'], 'ids' => array_values(array_diff($e['ids'], [$p->id])), 'locked' => false];
                }
            }
            if ($play->isLive()) {
                $board['queue'] = $queue;
            }
        });
    }

    /**
     * Run $fn on a locked, fresh copy of this row and its board, then save.
     * Auto-fill tops up open courts after every change.
     */
    private function mutate(callable $fn): mixed
    {
        return DB::transaction(function () use ($fn) {
            $play = self::query()->lockForUpdate()->findOrFail($this->id);
            $board = $play->board ?? ['seq' => 0, 'queue' => [], 'courts' => []];

            $result = $fn($board, $play);

            if ($play->status === 'live' && $play->auto_fill) {
                foreach ($board['courts'] as $court) {
                    if (! $court['a'] && ! self::seatNextUp($board, $court['n'])) {
                        break;
                    }
                }
            }

            $play->board = $board;
            $play->save();

            $this->setRawAttributes($play->getAttributes(), true);
            $this->participantCache = null;

            return $result;
        });
    }

    private static function seatNextUp(array &$board, int $n): ?array
    {
        $next = self::nextUp($board['queue']);
        if (! $next) {
            return null;
        }
        foreach ($board['courts'] as &$court) {
            if ($court['n'] === $n && ! $court['a']) {
                $court = ['n' => $n, 'a' => $next['a'], 'b' => $next['b'], 'started_at' => now()->timestamp, 'wins' => 0];
                $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => ! in_array($e['id'], $next['used'])));

                return $next;
            }
        }

        return null;
    }
}
