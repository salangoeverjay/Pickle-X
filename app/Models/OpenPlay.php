<?php

namespace App\Models;

use App\Support\Emails;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * An open play session and, while live, its court board.
 *
 * board = [
 *   'seq'    => int,  // last queue entry id handed out
 *   'queue'  => [['id' => int, 'ids' => [participantId, ...], 'locked' => bool], ...],
 *   'courts' => [['n' => 1, 'a' => ['ids' => [..], 'locked' => bool]|null, 'b' => ..., 'started_at' => unix|null, 'wins' => int, 'w0' => unix|null], ...],
 *                started_at is when the game clock starts: a new foursome warms up first (from w0), so it's in the future until then.
 * ]
 *
 * A queue entry is one waiting player, or two locked partners who always play together.
 * Entries may carry 'paused' => true (stepped out / subbed out: keeps its spot, skipped).
 * Only checked-in participants (participants.checked_in_at) are called to court.
 * ponytail: the board is one JSON column guarded by a row lock; fine for one organizer
 * per session, move queue/courts to tables if many writers ever edit one board at once.
 */
class OpenPlay extends Model
{
    protected $fillable = [
        'organizer_id', 'title', 'club', 'venue', 'starts_at', 'ends_at', 'courts', 'cap',
        'race_to', 'rotation', 'warmup', 'visibility', 'fee', 'status', 'auto_fill', 'board', 'started_at', 'ended_at', 'share_code',
        'pay_method', 'pay_name', 'pay_number', 'pay_qr_version', 'link_code', 'link_guests',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'auto_ended' => 'boolean',
            'auto_fill' => 'boolean',
            'link_guests' => 'boolean',
            'warmup' => 'integer',
            'board' => 'array',
        ];
    }

    private ?Collection $participantCache = null;

    // Payment QR methods: [key => [label, app name used in "Open {app} and scan the QR"]].
    public const PAY_METHODS = [
        'gcash' => ['GCash', 'GCash'],
        'maya' => ['Maya', 'Maya'],
        'qrph' => ['Bank / QR Ph', 'your bank or e-wallet app'],
        'other' => ['Other', 'your payment app'],
    ];

    // ---------------------------------------------------------------- relations

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /** Up to 3 accounts who can start this open play and run its court board (OpenPlay::MAX_COHOSTS). */
    public function cohosts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'open_play_cohosts')->withTimestamps();
    }

    public const MAX_COHOSTS = 3;

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /** The host or a co-host: they start it, run the court board and share the link. Only the host edits, deletes or changes co-hosts. */
    public function isRunBy(?User $user): bool
    {
        return $user && ($this->organizer_id === $user->id || $this->isCohost($user));
    }

    public function isCohost(?User $user): bool
    {
        return $user && $this->cohosts()->whereKey($user->id)->exists();
    }

    /** Open plays $user runs: hosts or co-hosts. */
    public function scopeRunBy(Builder $query, User $user): void
    {
        $query->where(fn ($q) => $q->where('organizer_id', $user->id)->orWhereHas('cohosts', fn ($c) => $c->whereKey($user->id)));
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

    /** What a member sees: public open plays, plus invite-only ones they joined through the link. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(fn ($q) => $q->where('visibility', 'public')
            ->orWhereHas('participants', fn ($p) => $p->where('user_id', $user->id)->whereNull('left_at'))
            ->orWhereHas('cohosts', fn ($c) => $c->whereKey($user->id)));
    }

    // ---------------------------------------------------------------- share link

    /** The open play's share link code (/j/{code}), made the first time it's asked for. */
    public function linkCode(): string
    {
        if (! $this->link_code) {
            $this->forceFill(['link_code' => self::newLinkCode()])->save();
        }

        return $this->link_code;
    }

    public function linkUrl(): string
    {
        return route('join', $this->linkCode());
    }

    /** A new link: the old one stops working. Everyone who already joined stays in. */
    public function resetLink(): void
    {
        $this->forceFill(['link_code' => self::newLinkCode()])->save();
    }

    /** The message organizers and players paste into Messenger, Viber or a group chat. */
    public function shareMessage(): string
    {
        return "Join me at {$this->title}, {$this->whenLabel()} · {$this->timeRange()} at {$this->venue}. "
            .($this->fee ? "{$this->feeLabel()} per player" : 'Free').'. Join here: '.preg_replace('#^https?://#', '', $this->linkUrl());
    }

    private static function newLinkCode(): string
    {
        do {
            $code = collect(range(1, 6))->map(fn () => 'abcdefghjkmnpqrstuvwxyz23456789'[random_int(0, 30)])->join('');
        } while (self::where('link_code', $code)->exists());

        return $code;
    }

    /** Someone without an account joins through the share link: a named guest on the player list (not while live). */
    public function addLinkGuest(string $name, ?string $email): Participant
    {
        return $this->mutate(function (array &$board, self $play) use ($name, $email) {
            if (! $play->link_guests) {
                throw new InvalidArgumentException('The organizer asks everyone to join with a PickleX account.');
            }
            if ($play->status !== 'upcoming') {
                throw new InvalidArgumentException("It's on now. No account? Ask the organizer at the courts to add you as a walk-in.");
            }
            if ($play->activeParticipants()->whereNull('user_id')->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists()) {
                throw new InvalidArgumentException("Someone named {$name} already joined as a guest. Add your last name or an initial.");
            }
            if ($play->activeParticipants()->count() >= $play->cap) {
                throw new InvalidArgumentException('Sorry, the last spot was just taken.');
            }

            return $play->participants()->create(['name' => $name, 'email' => $email ?: null, 'via_link' => true]);
        });
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

    /** Warmup lengths an organizer can pick, in minutes (0 = off). */
    public const WARMUPS = [0 => 'Off', 2 => '2 min', 3 => '3 min', 5 => '5 min'];

    /** "3 min warmup" or "No warmup". */
    public function warmupLabel(): string
    {
        return $this->warmup ? "{$this->warmup} min warmup" : 'No warmup';
    }

    /** A court whose foursome is still warming up (its game clock hasn't started). */
    public static function isWarming(array $court): bool
    {
        return $court['a'] && $court['started_at'] > now()->timestamp;
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

    /** "6:00 - 10:00 PM", "11:00 AM - 1:00 PM" across noon, "8:00 PM - 1:00 AM (next day)" overnight. */
    public function timeRange(): string
    {
        return self::rangeLabel($this->starts_at, $this->ends_at);
    }

    /** timeRange() for any two times (the create/edit review uses it before saving). */
    public static function rangeLabel(CarbonInterface $starts, CarbonInterface $ends): string
    {
        $sameHalf = $starts->format('A') === $ends->format('A');
        $range = $starts->format($sameHalf ? 'g:i' : 'g:i A').' - '.$ends->format('g:i A');

        return $ends->isSameDay($starts) ? $range : $range.' (next day)';
    }

    /** "3 hrs", "1 hr 30 min". */
    public static function lengthLabel(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? $h.' '.($h === 1 ? 'hr' : 'hrs') : '').($m ? " {$m} min" : '')) ?: '0 min';
    }

    /** Joined players still in the session. Uses withCount('activeParticipants') when loaded. */
    public function playerCount(): int
    {
        return $this->active_participants_count ?? $this->activeParticipants()->count();
    }

    public function hasPaymentQr(): bool
    {
        return (bool) $this->pay_qr_version;
    }

    public function payMethodLabel(): string
    {
        return self::PAY_METHODS[$this->pay_method][0] ?? 'Payment';
    }

    public function payApp(): string
    {
        return self::PAY_METHODS[$this->pay_method][1] ?? 'your payment app';
    }

    /** The QR image; append ?download=1 to save it. */
    public function payQrUrl(): ?string
    {
        return $this->pay_qr_version ? route('play.pay-qr', ['play' => $this->id, 'version' => $this->pay_qr_version]) : null;
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
        // Each player as others see them (full name, display name, or the guest's typed name): "Kai Santos & Test 123".
        return collect($ids)->map(fn ($id) => $this->participant($id)?->name ?? 'Guest')->join(', ', ' & ');
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
        return self::nextUp($this->queue(), $this->readyIds(), $this->fairBook());
    }

    /**
     * Everyone rotates needs tonight's history: games played per participant (finished games),
     * and how often each pair partnered or faced each other (finished games plus the ones on court).
     * Null for winners stay, which calls players in line order.
     *
     * @return array{sg: array<int, int>, pc: array<string, int>, oc: array<string, int>}|null
     */
    public function fairBook(?array $board = null): ?array
    {
        if ($this->rotation === 'winners') {
            return null;
        }
        $games = GamePlayer::query()->whereIn('game_id', $this->games()->select('id'))->get(['game_id', 'team', 'participant_id'])
            ->groupBy('game_id')->map(fn ($ps) => [$ps->where('team', 'a')->pluck('participant_id')->all(), $ps->where('team', 'b')->pluck('participant_id')->all()])
            ->values()->all();
        // Games per player, plus the games late arrivals were credited with (levelUp).
        $board ??= $this->board;
        $sg = $board['par'] ?? [];
        foreach ($games as [$a, $b]) {
            foreach ([...$a, ...$b] as $id) {
                $sg[$id] = ($sg[$id] ?? 0) + 1;
            }
        }
        foreach ($board['courts'] ?? [] as $c) {
            if ($c['a']) {
                $games[] = [$c['a']['ids'], $c['b']['ids']];
            }
        }
        $pc = $oc = [];
        foreach ($games as [$a, $b]) {
            foreach ([$a, $b] as $team) {
                if (count($team) === 2) {
                    $k = self::pairKey(...$team);
                    $pc[$k] = ($pc[$k] ?? 0) + 1;
                }
            }
            foreach ($a as $x) {
                foreach ($b as $y) {
                    $k = self::pairKey($x, $y);
                    $oc[$k] = ($oc[$k] ?? 0) + 1;
                }
            }
        }

        return ['sg' => $sg, 'pc' => $pc, 'oc' => $oc];
    }

    public static function pairKey(int $x, int $y): string
    {
        return min($x, $y).'|'.max($x, $y);
    }

    /** Partners each participant has had tonight, oldest first (finished games, then the one on court). */
    public function partnersTonight(int $participantId): array
    {
        $mine = GamePlayer::query()->whereIn('game_id', $this->games()->select('id'))->where('participant_id', $participantId)->orderBy('game_id')->get(['game_id', 'team']);
        $mates = GamePlayer::query()->whereIn('game_id', $mine->pluck('game_id'))->where('participant_id', '!=', $participantId)->get(['game_id', 'team', 'participant_id'])
            ->filter(fn ($g) => $mine->firstWhere('game_id', $g->game_id)?->team === $g->team)->sortBy('game_id')->pluck('participant_id')->all();
        foreach ($this->boardCourts() as $c) {
            foreach ($c['a'] ? [$c['a']['ids'], $c['b']['ids']] : [] as $team) {
                if (in_array($participantId, $team)) {
                    array_push($mates, ...array_diff($team, [$participantId]));
                }
            }
        }

        return array_values($mates);
    }

    /**
     * Who plays next: a full game when 4 are ready, otherwise the ready entries at the front
     * of the line and how many more checked-in players are needed. Null when nobody is ready.
     *
     * @return array{full: bool, used: int[], need: int, a?: array, b?: array}|null
     */
    public function nextGroup(): ?array
    {
        $queue = $this->queue();
        $ready = $this->readyIds();
        if ($full = self::nextUp($queue, $ready, $this->fairBook())) {
            return $full + ['full' => true, 'need' => 0];
        }

        $used = [];
        $n = 0;
        foreach ($queue as $e) {
            if ($n >= 4) {
                break;
            }
            if (self::isAvailable($e, $ready) && $n + count($e['ids']) <= 4) {
                $used[] = $e['id'];
                $n += count($e['ids']);
            }
        }

        return $used ? ['full' => false, 'used' => $used, 'need' => 4 - $n] : null;
    }

    /** Entries ahead of the last one called next that are being skipped: [names, anyPaused]. */
    public function skippedForNext(array $group): array
    {
        $queue = $this->queue();
        $last = array_search(end($group['used']), array_column($queue, 'id'));
        $ready = $this->readyIds();
        $skipped = array_filter(array_slice($queue, 0, $last === false ? 0 : $last + 1), fn ($e) => ! self::isAvailable($e, $ready));

        return [collect($skipped)->map(fn ($e) => $this->names($e['ids']))->join(', '), collect($skipped)->contains(fn ($e) => $e['paused'] ?? false)];
    }

    /** Everyone rotates: ready entries ahead of the last one called next that wait a turn (more games tonight, or a repeat partner). */
    public function passedForNext(array $group): array
    {
        $queue = $this->queue();
        $last = array_search(end($group['used']), array_column($queue, 'id'));
        $ready = $this->readyIds();

        return collect(array_slice($queue, 0, $last === false ? 0 : $last))
            ->filter(fn ($e) => self::isAvailable($e, $ready) && ! in_array($e['id'], $group['used']))
            ->flatMap(fn ($e) => $e['ids'])->values()->all();
    }

    /** Checked-in participants still in the session (from the loaded map). */
    public function readyIds(): array
    {
        return $this->participantMap()->filter(fn ($p) => $p->checked_in_at && ! $p->left_at)->keys()->all();
    }

    /** Everyone on court or in the queue right now. */
    public function sessionPlayerIds(): array
    {
        $ids = collect($this->boardCourts())->filter(fn ($c) => $c['a'])->flatMap(fn ($c) => [...$c['a']['ids'], ...$c['b']['ids']]);

        return $ids->merge(collect($this->queue())->flatMap(fn ($e) => $e['ids']))->all();
    }

    public function checkedInCount(): int
    {
        $ready = $this->readyIds();

        return count(array_intersect($this->sessionPlayerIds(), $ready));
    }

    /** Players in the queue who can be called now (checked in, not stepped out). */
    public function readyQueueCount(): int
    {
        $ready = $this->readyIds();

        return collect($this->queue())->filter(fn ($e) => self::isAvailable($e, $ready))->sum(fn ($e) => count($e['ids']));
    }

    /**
     * Who's in, as players see it. Live: on court, then the queue, then anyone who joined but isn't on the board,
     * each with 'st' court | line | out (stepped out) | away (not checked in), plus 'court' or 'pos'.
     * Upcoming: everyone in the order they joined ('st' => 'in').
     *
     * @return \Illuminate\Support\Collection<int, array{p: Participant, st: string, court?: int, pos?: int}>
     */
    public function whoIsIn(): \Illuminate\Support\Collection
    {
        $active = $this->participantMap()->whereNull('left_at');
        if (! $this->isLive()) {
            return $active->sortBy(fn ($p) => [$p->created_at, $p->id])->values()->map(fn ($p) => ['p' => $p, 'st' => 'in']);
        }

        $out = [];
        foreach ($this->boardCourts() as $c) {
            foreach ($c['a'] ? [...$c['a']['ids'], ...$c['b']['ids']] : [] as $id) {
                $out[$id] ??= ['st' => 'court', 'court' => $c['n']];
            }
        }
        foreach ($this->queue() as $i => $e) {
            foreach ($e['ids'] as $id) {
                $here = $active->get($id)?->checked_in_at;
                $out[$id] ??= ['st' => ($e['paused'] ?? false) ? 'out' : ($here ? 'line' : 'away'), 'pos' => $i + 1];
            }
        }
        foreach ($active->keys() as $id) {
            $out[$id] ??= ['st' => 'away'];
        }

        return collect($out)->filter(fn ($r, $id) => $active->has($id))->map(fn ($r, $id) => ['p' => $active->get($id)] + $r)->values();
    }

    /** Public view-mode URL (QR target). */
    public function shareUrl(): ?string
    {
        return $this->share_code ? route('live', $this->share_code) : null;
    }

    /** Index of the court that has been playing longest (likely next to open). */
    public function longestCourtIndex(): ?int
    {
        $busy = collect($this->boardCourts())->filter(fn ($c) => $c['a'] && empty($c['closing']));

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
     * Tonight's standings: most wins, then fewest losses, then point difference, then points scored.
     * Each row: participant_id, w, l, g, pf, pa, diff, rank (1-based).
     */
    public function standings(): array
    {
        $rows = GamePlayer::query()
            ->join('games', 'games.id', '=', 'game_players.game_id')
            ->where('games.open_play_id', $this->id)
            ->selectRaw("participant_id, sum(won) as w, sum(1 - won) as l,
                sum(case when team = 'a' then score_a else score_b end) as pf,
                sum(case when team = 'a' then score_b else score_a end) as pa")
            ->groupBy('participant_id')
            ->get()
            ->map(fn ($r) => [
                'participant_id' => (int) $r->participant_id,
                'w' => (int) $r->w, 'l' => (int) $r->l, 'g' => (int) $r->w + (int) $r->l,
                'pf' => (int) $r->pf, 'pa' => (int) $r->pa, 'diff' => (int) $r->pf - (int) $r->pa,
            ])
            ->sort(fn ($a, $b) => [$b['w'], $a['l'], $b['diff'], $b['pf'], $a['participant_id']] <=> [$a['w'], $b['l'], $a['diff'], $a['pf'], $b['participant_id']])
            ->values();

        return $rows->map(fn ($r, $i) => $r + ['rank' => $i + 1])->all();
    }

    /**
     * Where a member stands in this live session, or null if they are not in it.
     *
     * ['state' => 'court', 'court' => n, 'index' => i, 'partner' => ?, 'opponents' => ..., 'started_at' => unix]
     * ['state' => 'next', 'partner' => ?, 'opponents' => ...]
     * ['state' => 'queued', 'position' => n, 'ahead' => n]
     * ['state' => 'checkin', 'position' => n]   in line but not checked in yet
     * ['state' => 'paused', 'position' => n, 'entry' => id]   stepped out / subbed out, keeps the spot
     * Every state also has 'checked_in_at' (Carbon|null).
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

        $place = $this->placeOf($me);

        return $place ? $place + ['checked_in_at' => $me->checked_in_at] : null;
    }

    private function placeOf(Participant $me): ?array
    {

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
        $group = $this->nextGroup();
        $next = $group && $group['full'] ? $group : null;
        $free = collect($this->boardCourts())->first(fn ($c) => ! $c['a']);
        foreach ($this->queue() as $i => $entry) {
            if (in_array($me->id, $entry['ids'])) {
                if (! $me->checked_in_at) {
                    return ['state' => 'checkin', 'position' => $i + 1];
                }
                if ($entry['paused'] ?? false) {
                    return ['state' => 'paused', 'position' => $i + 1, 'entry' => $entry['id']];
                }
                if ($next && in_array($entry['id'], $next['used'])) {
                    $mine = in_array($me->id, $next['a']['ids']) ? $next['a'] : $next['b'];
                    $opp = $mine === $next['a'] ? $next['b'] : $next['a'];

                    return [
                        'state' => 'next',
                        'partner' => $this->names(array_diff($mine['ids'], [$me->id])) ?: null,
                        'opponents' => $this->names($opp['ids']),
                        'court' => $free['n'] ?? null,
                    ];
                }
                // First in line, but fewer than 4 players are ready to fill a game.
                if ($group && ! $group['full'] && in_array($entry['id'], $group['used'])) {
                    return ['state' => 'waiting', 'position' => $i + 1, 'need' => $group['need']];
                }

                return ['state' => 'queued', 'position' => $i + 1, 'ahead' => $ahead];
            }
            $ahead += count($entry['ids']);
        }

        return null;
    }

    // ---------------------------------------------------------------- queue rules

    /** Can this queue entry be called? $ready = checked-in participant ids, null = everyone. */
    public static function isAvailable(array $entry, ?array $ready): bool
    {
        return ! ($entry['paused'] ?? false) && ($ready === null || ! array_diff($entry['ids'], $ready));
    }

    /**
     * The next two teams. With $book (everyone rotates, see fairBook) the fairest four play; otherwise
     * the front of the queue, skipping entries that aren't available: locked pairs are a team, single
     * players pair up in queue order.
     *
     * @return array{a: array, b: array, used: int[], fair?: bool, rep?: int[]}|null each team also has 'eids' (its queue entry ids)
     */
    public static function nextUp(array $queue, ?array $ready = null, ?array $book = null): ?array
    {
        if ($book && ($fair = self::fairNext($queue, $ready, $book)) !== false) {
            return $fair;
        }

        $teams = [];
        $used = [];
        $single = null;

        foreach ($queue as $entry) {
            if (count($teams) === 2) {
                break;
            }
            if (! self::isAvailable($entry, $ready)) {
                continue;
            }
            if ($entry['locked'] && count($entry['ids']) === 2) {
                $teams[] = ['ids' => $entry['ids'], 'locked' => true, 'eids' => [$entry['id']]];
                $used[] = $entry['id'];
            } elseif ($single) {
                $teams[] = ['ids' => [$single['ids'][0], $entry['ids'][0]], 'locked' => false, 'eids' => [$single['id'], $entry['id']]];
                array_push($used, $single['id'], $entry['id']);
                $single = null;
            } else {
                $single = $entry;
            }
        }

        return count($teams) === 2 ? ['a' => $teams[0], 'b' => $teams[1], 'used' => $used] : null;
    }

    /**
     * Everyone rotates, fairly:
     *  1. Fewest games tonight play first: anyone with fewer games than the fourth player in that order is in.
     *  2. Among players tied on games (the 8 who have waited longest), pick the group and split the teams so
     *     nobody goes straight back on after a game (the last 4 in line just came off), then partners repeat as
     *     little as possible, then by wait, then opponents. Over a night everyone partners with as many
     *     different players as possible.
     *  Late arrivals start at par (see levelUp), so they rotate in instead of playing every game to catch up.
     * Locked pairs always play together and count as one pick. Null when fewer than 4 are ready,
     * false when no split works (line order takes over).
     */
    private static function fairNext(array $queue, ?array $ready, array $book): array|false|null
    {
        $units = [];
        foreach ($queue as $i => $e) {
            if (self::isAvailable($e, $ready)) {
                $n = count($e['ids']);
                $units[] = ['e' => $e, 'i' => $i, 'n' => $n, 'locked' => $e['locked'] && $n === 2, 'g' => max(array_map(fn ($id) => $book['sg'][$id] ?? 0, $e['ids']))];
            }
        }
        if (array_sum(array_column($units, 'n')) < 4) {
            return null;
        }

        usort($units, fn ($x, $y) => [$x['g'], $x['i']] <=> [$y['g'], $y['i']]);
        foreach ($units as $r => &$u) {
            $u['r'] = $r;
        }
        unset($u);
        $n = 0;
        foreach ($units as $cut) {
            if (($n += $cut['n']) >= 4) {
                break;
            }
        }
        $must = array_values(array_filter($units, fn ($u) => $u['g'] < $cut['g']));
        $tie = array_slice(array_values(array_filter($units, fn ($u) => $u['g'] === $cut['g'])), 0, 8);
        $need = 4 - array_sum(array_column($must, 'n'));
        $pc = fn ($t) => $book['pc'][self::pairKey($t[0], $t[1])] ?? 0;
        // Rest comes before new partners: nobody plays back to back unless nobody else can go on.
        $justOff = array_slice(array_merge(...array_map(fn ($e) => $e['ids'], $queue) ?: [[]]), -4);

        $best = null;
        $try = function (array $grp) use (&$best, $pc, $book, $justOff) {
            $lk = array_values(array_filter($grp, fn ($u) => $u['locked']));
            $sn = array_merge(...array_map(fn ($u) => $u['e']['ids'], array_values(array_filter($grp, fn ($u) => ! $u['locked']))) ?: [[]]);
            $splits = match (count($lk)) {
                2 => [[$lk[0]['e']['ids'], $lk[1]['e']['ids'], true, true]],
                1 => [[$lk[0]['e']['ids'], $sn, true, false]],
                default => [[[$sn[0], $sn[1]], [$sn[2], $sn[3]], false, false], [[$sn[0], $sn[2]], [$sn[1], $sn[3]], false, false], [[$sn[0], $sn[3]], [$sn[1], $sn[2]], false, false]],
            };
            $wait = array_sum(array_column($grp, 'r'));
            $tired = count(array_intersect(array_merge(...array_map(fn ($u) => $u['e']['ids'], $grp)), $justOff));
            foreach ($splits as $sp) {
                $ps = 0;
                foreach ([0, 1] as $t) {
                    if (! $sp[2 + $t]) {
                        $c = $pc($sp[$t]);
                        $ps += $c * ($c + 1);
                    }
                }
                $os = 0;
                foreach ($sp[0] as $x) {
                    foreach ($sp[1] as $y) {
                        $os += $book['oc'][self::pairKey($x, $y)] ?? 0;
                    }
                }
                $score = $tired * 1_000_000 + $ps * 1000 + $wait * 10 + $os;
                if (! $best || $score < $best['score']) {
                    $best = ['score' => $score, 'sp' => $sp, 'grp' => $grp];
                }
            }
        };
        $pick = function (int $start, array $chosen, int $left) use (&$pick, $try, $tie, $must) {
            if ($left === 0) {
                $try([...$must, ...$chosen]);

                return;
            }
            for ($j = $start; $j < count($tie); $j++) {
                if ($tie[$j]['n'] <= $left) {
                    $pick($j + 1, [...$chosen, $tie[$j]], $left - $tie[$j]['n']);
                }
            }
        };
        $pick(0, [], $need);
        if (! $best) {
            return false;
        }

        $team = function (array $ids, bool $locked) use ($best, $pc) {
            $us = array_values(array_filter($best['grp'], fn ($u) => array_intersect($u['e']['ids'], $ids)));

            return ['ids' => array_values($ids), 'locked' => $locked, 'eids' => array_map(fn ($u) => $u['e']['id'], $us),
                'r' => min(array_column($us, 'r')), 'rep' => $locked ? -1 : $pc($ids)];
        };
        [$a, $b] = [$team($best['sp'][0], $best['sp'][2]), $team($best['sp'][1], $best['sp'][3])];
        if ($b['r'] < $a['r']) {
            [$a, $b] = [$b, $a];
        }
        $eids = array_map(fn ($u) => $u['e']['id'], $best['grp']);
        $used = array_values(array_filter(array_column($queue, 'id'), fn ($id) => in_array($id, $eids)));
        $strip = fn ($t) => ['ids' => $t['ids'], 'locked' => $t['locked'], 'eids' => $t['eids']];

        return ['a' => $strip($a), 'b' => $strip($b), 'used' => $used, 'fair' => true, 'rep' => [$a['rep'], $b['rep']]];
    }

    /** The next single team (for winners-stay challengers). */
    public static function nextTeam(array $queue, ?array $ready = null): ?array
    {
        $single = null;

        foreach ($queue as $entry) {
            if (! self::isAvailable($entry, $ready)) {
                continue;
            }
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
    public function start(?User $by = null): void
    {
        if ($this->status !== 'upcoming') {
            throw new InvalidArgumentException('Only upcoming open plays can start.');
        }

        $this->mutate(function (array &$board, self $play) use ($by) {
            $board = ['seq' => 0, 'queue' => [], 'courts' => []];
            foreach (range(1, max(1, $play->courts)) as $n) {
                $board['courts'][] = ['n' => $n, 'a' => null, 'b' => null, 'started_at' => null, 'wins' => 0];
            }
            foreach ($play->activeParticipants()->orderBy('id')->pluck('id') as $id) {
                $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$id], 'locked' => false];
            }
            $play->forceFill(['status' => 'live', 'started_at' => now(), 'started_by' => $by?->id, 'auto_fill' => true, 'share_code' => $play->share_code ?? self::makeShareCode($play->club)]);
        });
    }

    public function end(): void
    {
        $this->update(['status' => 'done', 'ended_at' => now()]);
    }

    /** A live session nobody ended closes on its own this long after its end time. */
    public const AUTO_END_HOURS = 12;

    public function autoEndAt(): CarbonInterface
    {
        return $this->ends_at->copy()->addHours(self::AUTO_END_HOURS);
    }

    /**
     * Ends a session still live 12 hours after its end time, the same as End session (games and
     * standings saved, recaps sent), and tells the organizer. Run by open-plays:auto-end.
     */
    public function endAutomatically(): void
    {
        $this->forceFill(['auto_ended' => true])->end();
        Emails::sendRecaps($this->fresh());
        if ($this->organizer) {
            Emails::send($this->organizer, Emails::autoEnded($this->fresh()));
        }
    }

    public function setAutoFill(bool $on): void
    {
        $this->mutate(fn (array &$board, self $play) => $play->auto_fill = $on);
    }

    /** Put the next two teams on court $n. Returns the teams, or null if fewer than 4 are waiting. */
    public function fillCourt(int $n): ?array
    {
        return $this->mutate(fn (array &$board, self $play) => self::seatNextUp($board, $n, $play->checkedInIds(), $play));
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
                'court' => $n, 'score_a' => $scoreA, 'score_b' => $scoreB, 'winner' => $side, 'subs' => $court['subs'] ?? null,
                // Entered during warmup: the game clock starts now. Warmup time is never counted as play time.
                'started_at' => now()->setTimestamp(min($court['started_at'] ?: now()->timestamp, now()->timestamp)), 'played_at' => now(),
                'warmup_seconds' => ($court['w0'] ?? null) ? max(0, min($court['started_at'], now()->timestamp) - $court['w0']) : null,
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

            $closing = ! empty($board['courts'][$i]['closing']);
            if ($play->rotation === 'winners' && $wins < 2 && ! $closing) {
                // Winners stay on (as team A) for up to two straight games.
                $requeue($lose);
                if ($challenger = self::nextTeam($board['queue'], $play->checkedInIds())) {
                    $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => ! in_array($e['id'], $challenger['used'])));
                    // Winners who stay on skip the warmup.
                    $court = ['n' => $n, 'a' => $win, 'b' => $challenger['team'], 'started_at' => now()->timestamp, 'wins' => $wins, 'w0' => null];
                } else {
                    $requeue($win);
                }
            } else {
                // Everyone goes to the back, fewest games tonight first, so with a short queue the
                // same four don't keep playing (winners first used to keep winners on every game).
                $from = count($board['queue']);
                $requeue($win);
                $requeue($lose);
                $played = $play->gamesTonight();
                $back = array_slice($board['queue'], $from);
                usort($back, fn ($x, $y) => max(array_map(fn ($id) => $played[$id] ?? 0, $x['ids'])) <=> max(array_map(fn ($id) => $played[$id] ?? 0, $y['ids'])));
                array_splice($board['queue'], $from, count($back), $back);
            }

            // A court the organizer asked to close goes once its game is in.
            if ($closing) {
                array_splice($board['courts'], $i, 1);
                $play->courts = count($board['courts']);
            } else {
                $board['courts'][$i] = $court;
            }

            return $game;
        });
    }

    /** Courts tonight that aren't closing. At least one has to stay. */
    private static function openCourtCount(array $board, ?int $except = null): int
    {
        return collect($board['courts'])->filter(fn ($c) => $c['n'] !== $except && empty($c['closing']))->count();
    }

    /** The venue gave another court: it opens by its number, and auto-fill puts the next group on. */
    public function addCourt(int $n): void
    {
        $this->mutate(function (array &$board, self $play) use ($n) {
            if ($n < 1 || $n > 99 || collect($board['courts'])->contains('n', $n)) {
                throw new InvalidArgumentException("Court {$n} is already on the board.");
            }
            $board['courts'][] = ['n' => $n, 'a' => null, 'b' => null, 'started_at' => null, 'wins' => 0];
            usort($board['courts'], fn ($x, $y) => $x['n'] <=> $y['n']);
            $play->courts = count($board['courts']);
        });
    }

    /**
     * Take a court off. An open court goes now. With a game on: 'finish' lets it finish (the court closes when
     * the score goes in and nobody new goes on it), 'now' stops it (not counted, the four go to the front of the line).
     */
    public function removeCourt(int $n, string $how = 'finish'): void
    {
        $this->mutate(function (array &$board, self $play) use ($n, $how) {
            $i = collect($board['courts'])->search(fn ($c) => $c['n'] === $n);
            if ($i === false || ! self::openCourtCount($board, $n)) {
                throw new InvalidArgumentException('One court has to stay. To stop, end the session.');
            }
            $court = $board['courts'][$i];
            if ($court['a'] && $how === 'finish') {
                $board['courts'][$i]['closing'] = true;

                return;
            }
            if ($court['a']) {
                $front = [];
                foreach ([$court['a'], $court['b']] as $team) {
                    foreach ($team['locked'] && count($team['ids']) === 2 ? [$team['ids']] : array_map(fn ($id) => [$id], $team['ids']) as $ids) {
                        $front[] = ['id' => ++$board['seq'], 'ids' => $ids, 'locked' => count($ids) === 2];
                    }
                }
                $board['queue'] = [...$front, ...$board['queue']];
            }
            array_splice($board['courts'], $i, 1);
            $play->courts = count($board['courts']);
        });
    }

    /** Undo "closes after this game". */
    public function keepCourt(int $n): void
    {
        $this->mutate(function (array &$board) use ($n) {
            foreach ($board['courts'] as &$court) {
                if ($court['n'] === $n) {
                    unset($court['closing']);
                }
            }
        });
    }

    /** True when two queue entries share an id (boards hit by the old court-sub bug). Saving the board renumbers them. */
    public function repairQueueIds(): void
    {
        $ids = array_column($this->queue(), 'id');
        if (count($ids) !== count(array_unique($ids))) {
            $this->mutate(fn () => null);
        }
    }

    /** Change the warmup mid-session. It applies to the next foursome that goes on; courts warming up keep their countdown. */
    public function setWarmup(int $minutes): void
    {
        abort_unless(isset(self::WARMUPS[$minutes]), 422);
        $this->update(['warmup' => $minutes]);
    }

    /** Skip the rest of a court's warmup: the game clock starts now. Returns how long they warmed up (seconds), or null if it wasn't warming up. */
    public function startNow(int $n): ?int
    {
        return $this->mutate(function (array &$board) use ($n) {
            foreach ($board['courts'] as &$court) {
                if ($court['n'] === $n && self::isWarming($court)) {
                    $court['started_at'] = now()->timestamp;

                    return now()->timestamp - ($court['w0'] ?? now()->timestamp);
                }
            }

            return null;
        });
    }

    /** Switch the game mode mid-session. It applies from the next game; games on court finish as they are. */
    public function setRotation(string $rotation): void
    {
        abort_unless(in_array($rotation, ['rotate', 'winners']), 422);
        $this->mutate(function (array &$board, self $play) use ($rotation) {
            $play->rotation = $rotation;
            foreach ($board['courts'] as &$court) {
                $court['wins'] = 0;
            }
        });
    }

    /**
     * Sub on a live court: $outIds (one player, or both of a team) come off and the checked-in queue entries
     * $inEntryIds take their places. The game keeps going and its result counts for whoever finishes it.
     * $after for those coming off: 'out' (stepped out at the back of the line), 'back' (back of the line, ready)
     * or 'leave' (done for the night). Subbing one partner of a locked pair unlocks them.
     */
    public function subOnCourt(int $n, array $outIds, array $inEntryIds, string $after): void
    {
        $this->mutate(function (array &$board, self $play) use ($n, $outIds, $inEntryIds, $after) {
            $i = collect($board['courts'])->search(fn ($c) => $c['n'] === $n);
            $court = $i === false ? null : $board['courts'][$i];
            $outIds = array_values(array_unique(array_map('intval', $outIds)));
            $inEntryIds = array_map('intval', $inEntryIds);
            $side = $court && $court['a'] ? (in_array($outIds[0] ?? 0, $court['a']['ids']) ? 'a' : (in_array($outIds[0] ?? 0, $court['b']['ids']) ? 'b' : null)) : null;
            $ins = collect($inEntryIds)->map(fn ($id) => collect($board['queue'])->firstWhere('id', $id))->filter()->values();
            $inIds = $ins->flatMap(fn ($e) => $e['ids'])->all();
            $ready = $play->checkedInIds();
            $ok = $side && in_array($after, ['out', 'back', 'leave']) && count($outIds) <= 2 && ! array_diff($outIds, $court[$side]['ids'])
                && $ins->count() === count($inEntryIds) && count($inIds) === count($outIds) && $ins->every(fn ($e) => self::isAvailable($e, $ready));
            if (! $ok) {
                throw new InvalidArgumentException('Pick who comes off and a checked-in player to go on.');
            }

            $swap = array_combine($outIds, $inIds);
            $team = $court[$side];
            $whole = count($outIds) === count($team['ids']);
            $court[$side] = ['ids' => array_map(fn ($id) => $swap[$id] ?? $id, $team['ids']), 'locked' => $whole && $ins->count() === 1 && $ins[0]['locked']];
            foreach ($outIds as $o) {
                $court['subs'][] = ['out' => $o, 'in' => $swap[$o], 'at' => now()->timestamp];
            }
            $board['courts'][$i] = $court;

            $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => ! in_array($e['id'], $inEntryIds)));
            if ($after === 'leave') {
                $play->participants()->whereIn('id', $outIds)->update(['left_at' => now()]);
            } else {
                // A loop, not an arrow fn: ++$board['seq'] has to move the real counter, or the next entry reuses this id.
                foreach ($whole && $team['locked'] ? [$outIds] : array_map(fn ($o) => [$o], $outIds) as $ids) {
                    $board['queue'][] = ['id' => ++$board['seq'], 'ids' => $ids, 'locked' => count($ids) === 2] + ($after === 'out' ? ['paused' => true] : []);
                }
            }
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
            $p = $play->participants()->create(['name' => $name, 'checked_in_at' => now()]);
            $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$p->id], 'locked' => false];
            self::levelUp($board, $play, [$p->id]);

            return $p;
        });
    }

    /** Mark participants as at the courts. Only checked-in players get called to play. */
    public function checkIn(array $participantIds): void
    {
        $this->mutate(function (array &$board, self $play) use ($participantIds) {
            $new = $play->participants()->whereIn('id', $participantIds)->whereNull('checked_in_at')->pluck('id')->all();
            $play->participants()->whereIn('id', $new)->update(['checked_in_at' => now()]);
            self::levelUp($board, $play, $new);
        });
    }

    /** Step an entry out (keeps its spot, skipped when calling games) or bring it back. */
    public function setPaused(int $entryId, bool $paused): void
    {
        $this->mutate(function (array &$board, self $play) use ($entryId, $paused) {
            foreach ($board['queue'] as &$e) {
                if ($e['id'] === $entryId) {
                    $e['paused'] = $paused;
                    $back = $paused ? [] : $e['ids'];
                }
            }
            unset($e);
            self::levelUp($board, $play, $back ?? []);
        });
    }

    /**
     * $inIds take $outId's place in line; $outId steps out right behind them and keeps its spot.
     * A single swaps with one single. A locked pair steps out together, so it takes two players back:
     * another locked pair, or two singles.
     */
    public function subIn(int $outId, int|array $inIds): void
    {
        $inIds = array_values(array_unique((array) $inIds));
        $this->mutate(function (array &$board) use ($outId, $inIds) {
            $queue = collect($board['queue']);
            $out = $queue->firstWhere('id', $outId);
            $ins = collect($inIds)->map(fn ($id) => $queue->firstWhere('id', $id))->filter();
            $pair = fn ($e) => $e['locked'] && count($e['ids']) === 2;
            $ok = $out && $ins->count() === count($inIds) && ! in_array($outId, $inIds)
                && $ins->sum(fn ($e) => count($e['ids'])) === count($out['ids'])
                && ($pair($out) ? $ins->count() === 1 ? $pair($ins[0]) : $ins->every(fn ($e) => count($e['ids']) === 1) : $ins->count() === 1);
            if (! $ok) {
                throw new InvalidArgumentException('Pick a waiting player to sub in.');
            }
            $queue = $queue->reject(fn ($e) => in_array($e['id'], $inIds))->values();
            $at = $queue->search(fn ($e) => $e['id'] === $outId);
            $queue->splice($at, 1, [...$ins->values()->all(), ['paused' => true] + $out]);
            $board['queue'] = $queue->all();
        });
    }

    /**
     * Add the organizer's paid list (rows from PaidList::rows). Linked members join as themselves,
     * everyone else as a named guest. Duplicate accounts are added once. Returns [added, linked].
     */
    public function addPaidPlayers(array $rows): array
    {
        $linked = 0;
        $added = 0;
        foreach (collect($rows)->unique(fn ($r) => $r['user']?->id ?? 'guest:'.mb_strtolower($r['line'])) as $row) {
            if ($row['user']) {
                // Members who already joined in the app keep that; everyone else came from the list.
                $p = $this->participants()->firstOrNew(['user_id' => $row['user']->id], ['from_list' => true]);
                $p->fill(['name' => $row['user']->name, 'paid' => true, 'left_at' => null])->save();
                $this->adoptClub($row['user']);
                $linked++;
            } else {
                $this->participants()->create(['name' => $row['line'], 'paid' => true, 'from_list' => true]);
            }
            $added++;
        }
        $this->participantCache = null;

        return [$added, $linked];
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
            $play->adoptClub($user);
            if ($play->isLive()) {
                $board['queue'][] = ['id' => ++$board['seq'], 'ids' => [$p->id], 'locked' => false];
                if ($p->checked_in_at) {
                    self::levelUp($board, $play, [$p->id]);
                }
            }

            return $p;
        });
    }

    /** Members without a home club get this open play's club, so they show in its rankings (MemberStats::ranking). */
    protected function adoptClub(User $user): void
    {
        if (! $user->club && $this->club) {
            $user->update(['club' => $this->club]);
        }
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

            // Every queue entry needs its own id (select, step out and sub all find entries by it). Boards that
            // got a repeated id from the old court-sub bug get the later one renumbered on their next change.
            $seen = [];
            $board['queue'] ??= [];
            $board['seq'] = max([$board['seq'] ?? 0, ...array_column($board['queue'], 'id')]);
            foreach ($board['queue'] as &$e) {
                if (isset($seen[$e['id']])) {
                    $e['id'] = ++$board['seq'];
                }
                $seen[$e['id']] = true;
            }
            unset($e);

            if ($play->status === 'live' && $play->auto_fill) {
                $ready = $play->checkedInIds();
                foreach ($board['courts'] as $court) {
                    if (! $court['a'] && ! self::seatNextUp($board, $court['n'], $ready, $play)) {
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

    /**
     * Late arrivals and players back from a break join the rotation at par: counted as having played as many
     * games as the fewest anyone else here has, so everyone rotates doesn't keep them on court until they catch up.
     */
    private static function levelUp(array &$board, self $play, array $ids): void
    {
        if ($play->status !== 'live' || ! $ids) {
            return;
        }
        $played = $play->gamesTonight();
        $par = $board['par'] ?? [];
        $games = fn ($id) => ($played[$id] ?? 0) + ($par[$id] ?? 0);
        $here = array_diff(array_intersect(self::boardPlayerIds($board), $play->checkedInIds()), $ids);
        if (! $here) {
            return;
        }
        $floor = min(array_map($games, $here));
        foreach ($ids as $id) {
            if ($games($id) < $floor) {
                $par[$id] = $floor - ($played[$id] ?? 0);
            }
        }
        $board['par'] = $par;
    }

    /** Everyone on court or in the queue on this board. */
    private static function boardPlayerIds(array $board): array
    {
        $ids = [];
        foreach ($board['courts'] ?? [] as $c) {
            if ($c['a']) {
                array_push($ids, ...$c['a']['ids'], ...$c['b']['ids']);
            }
        }
        foreach ($board['queue'] ?? [] as $e) {
            array_push($ids, ...$e['ids']);
        }

        return $ids;
    }

    /** Fresh from the DB (not the cached map): used inside writes. */
    private function checkedInIds(): array
    {
        return $this->participants()->whereNull('left_at')->whereNotNull('checked_in_at')->pluck('id')->all();
    }

    /** "PDS-7K2Q": club initials + random suffix. Unguessable enough for a read-only view. */
    private static function makeShareCode(string $club): string
    {
        $prefix = collect(preg_split('/\s+/', strtoupper(preg_replace('/[^A-Za-z ]/', '', $club))))->filter()->map(fn ($w) => $w[0])->take(3)->join('') ?: 'PX';

        do {
            $code = $prefix.'-'.strtoupper(Str::random(4));
        } while (self::where('share_code', $code)->exists());

        return $code;
    }

    private static function seatNextUp(array &$board, int $n, ?array $ready, self $play): ?array
    {
        $next = self::nextUp($board['queue'], $ready, $play->fairBook($board));
        if (! $next) {
            return null;
        }
        foreach ($board['courts'] as &$court) {
            if ($court['n'] === $n && ! $court['a']) {
                $warm = $play->warmup * 60;
                $court = ['n' => $n, 'a' => $next['a'], 'b' => $next['b'], 'started_at' => now()->timestamp + $warm, 'wins' => 0, 'w0' => $warm ? now()->timestamp : null];
                $board['queue'] = array_values(array_filter($board['queue'], fn ($e) => ! in_array($e['id'], $next['used'])));

                return $next;
            }
        }

        return null;
    }
}
