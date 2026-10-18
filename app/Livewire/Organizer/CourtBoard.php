<?php

namespace App\Livewire\Organizer;

use App\Livewire\Concerns\Pages;
use App\Models\OpenPlay;
use App\Models\User;
use App\Support\AccountMatch;
use App\Support\Emails;
use App\Support\UpNext;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Court board · PickleX')]
class CourtBoard extends Component
{
    use Pages;

    /** Mobile only: which column shows (courts|queue). */
    public string $tab = 'courts';

    /** Queue entry ids picked to lock as partners (max 2, oldest drops). */
    public array $selected = [];

    public string $walkin = '';

    /** Walk-in: account the organizer picked from the matches (waiting on "Yes, add"), and whether Add was pressed with matches unanswered. */
    public ?int $walkinPick = null;

    public bool $walkinTried = false;

    /** Court whose score sheet is open; null when closed. Null scores = blank input. */
    public ?int $scoreCourt = null;

    public ?int $scoreA = 0;

    public ?int $scoreB = 0;

    /** Up-next queue entry whose sub panel is open. */
    public ?int $subFor = null;

    /** Add or remove courts: ['step' => list|remove, 'num' => court number to add, 'court' => n being removed, 'how' => finish|now]. */
    public ?array $crt = null;

    /** Sub on a live court: ['court' => n, 'out' => participant ids, 'in' => queue entry ids, 'after' => out|back|leave]. */
    public ?array $csub = null;

    /** Big queues: find by name, filter (all|here|away|out), and how many spots show. */
    public string $qq = '';

    public string $qf = 'all';

    public int $qShow = self::QUEUE_PAGE;

    public const QUEUE_PAGE = 30;

    /** Game saved in this request, so its result row can animate in. */
    protected ?int $freshGame = null;

    /** Participant ids that just moved onto the podium (animated once). */
    protected array $podNew = [];

    /** Organizers, and members who co-host an open play that hasn't ended (they run it from player mode). */
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->isOrganizer() || OpenPlay::where('status', '!=', 'done')->whereHas('cohosts', fn ($q) => $q->whereKey($user->id))->exists(), 403);
    }

    /** The live session this account hosts or co-hosts. Querying through the plays they run is the authorization. */
    private function play(): OpenPlay
    {
        $play = OpenPlay::runBy(auth()->user())->status('live')->with('organizer', 'starter')->first();
        abort_unless($play, 403);

        return $play;
    }

    /** Run a board write; model rule violations come back as a toast. Then email whoever just became up next. */
    private function attempt(callable $fn): mixed
    {
        try {
            return tap($fn(), fn () => UpNext::notify($this->play()));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return false;
        }
    }

    public function toggleAutoFill(): void
    {
        $play = $this->play();
        $this->attempt(fn () => $play->setAutoFill(! $play->auto_fill));
    }

    public function endSession()
    {
        $play = $this->play();
        $games = $play->games()->count();
        $play->end();
        Emails::sendRecaps($play->fresh());

        session()->flash('toast', "Session ended · {$games} ".($games === 1 ? 'game' : 'games').' logged');

        return $this->redirectRoute('organizer.plays', navigate: true);
    }

    public function fillCourt(int $n): void
    {
        $play = $this->play();
        $teams = $this->attempt(fn () => $play->fillCourt($n));

        if ($teams === false) {
            return;
        }
        $this->dispatch('toast', message: $teams
            ? "Court {$n}: {$play->names($teams['a']['ids'])} vs {$play->names($teams['b']['ids'])}"
            : 'Need 4 checked-in players in the queue to fill a court');
    }

    public function toggleSelect(int $entryId): void
    {
        if (in_array($entryId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$entryId]));
        } else {
            $this->selected = array_slice([...$this->selected, $entryId], -2);
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function lockPartners(): void
    {
        $play = $this->play();
        $ids = collect($play->queue())->whereIn('id', $this->selected)->pluck('ids')->flatten()->all();

        if ($this->attempt(fn () => $play->lockPartners(...array_pad($this->selected, 2, 0))) !== false) {
            $this->dispatch('toast', message: $play->names($ids).' locked as partners');
            $this->selected = [];
        }
    }

    public function unlockEntry(int $entryId): void
    {
        $play = $this->play();

        if ($this->attempt(fn () => $play->unlockEntry($entryId)) !== false) {
            $this->dispatch('toast', message: 'Partners unlocked');
        }
    }

    public function removeEntry(int $entryId): void
    {
        $play = $this->play();
        $entry = collect($play->queue())->firstWhere('id', $entryId);

        if ($entry && $this->attempt(fn () => $play->removeEntry($entryId)) !== false) {
            $this->dispatch('toast', message: $play->names($entry['ids']).' left the queue');
            $this->selected = array_values(array_diff($this->selected, [$entryId]));
        }
    }

    public function checkInEntry(int $entryId): void
    {
        $play = $this->play();
        $entry = collect($play->queue())->firstWhere('id', $entryId);

        if ($entry && $this->attempt(fn () => $play->checkIn($entry['ids'])) !== false) {
            $this->dispatch('toast', message: $play->names($entry['ids']).' checked in');
        }
    }

    public function setPaused(int $entryId, bool $paused): void
    {
        $play = $this->play();
        $entry = collect($play->queue())->firstWhere('id', $entryId);
        $this->subFor = null;

        if ($entry && $this->attempt(fn () => $play->setPaused($entryId, $paused)) !== false) {
            $names = $play->names($entry['ids']);
            $two = count($entry['ids']) > 1;
            $this->dispatch('toast', message: $paused ? "{$names} stepped out · ".($two ? 'keep' : 'keeps').' their spot' : "{$names} ".($two ? 'are' : 'is').' back in the rotation');
        }
    }

    /** $inIds: one queue entry, or two singles when a locked pair steps out (they always step out together). */
    public function subIn(int $outId, int|array $inIds): void
    {
        $play = $this->play();
        $queue = collect($play->queue());
        $out = $queue->firstWhere('id', $outId);
        $ins = collect((array) $inIds)->map(fn ($id) => $queue->firstWhere('id', $id))->filter();
        $this->subFor = null;
        $this->selected = [];

        if ($out && $ins->isNotEmpty() && $this->attempt(fn () => $play->subIn($outId, $inIds)) !== false) {
            $inPids = $ins->flatMap(fn ($e) => $e['ids'])->all();
            $pair = count($out['ids']) === 2;
            $outNames = $play->names($out['ids']);
            $keeps = $pair ? "{$outNames} keep their spot" : ($play->participant($out['ids'][0])?->firstName() ?? 'Guest').' keeps their spot';
            $this->dispatch('toast', message: $play->names($inPids).(count($inPids) > 1 ? ' sub in for ' : ' subs in for ')."{$outNames} · {$keeps}");
        }
    }

    /** Everyone rotates or winners stay, switched mid-session from the Queue panel. */
    public function setRotation(string $rotation): void
    {
        $play = $this->play();
        if ($play->rotation === $rotation) {
            return;
        }
        $play->setRotation($rotation);
        $this->dispatch('toast', message: $play->rotationLabel().' from the next game · games on court finish as they are');
    }

    /** Warmup length, changed mid-session from the header. Applies to the next foursome that goes on. */
    public function setWarmup(int $minutes): void
    {
        $play = $this->play();
        if ($play->warmup === $minutes) {
            return;
        }
        $play->setWarmup($minutes);
        $this->dispatch('toast', message: ($minutes ? "{$minutes}-minute warmup" : 'No warmup').' from the next game · courts on now keep their clock');
    }

    /** Skip the rest of a court's warmup: the game clock starts now. */
    public function startNow(int $n): void
    {
        $warmed = $this->play()->startNow($n);
        if ($warmed !== null) {
            $this->dispatch('toast', message: "Court {$n}: game on · warmed up for ".sprintf('%02d:%02d', intdiv($warmed, 60), $warmed % 60));
        }
    }

    public function openCourts(): void
    {
        $taken = collect($this->play()->boardCourts())->pluck('n')->all();
        $this->crt = ['step' => 'list', 'num' => self::freeCourtNumber($taken, max([0, ...$taken]) + 1, 1) ?? 1];
    }

    /** Next free court number from $n going $d (+1 or -1), or null when there's none between 1 and 99. */
    private static function freeCourtNumber(array $taken, int $n, int $d): ?int
    {
        while ($n >= 1 && $n <= 99 && in_array($n, $taken)) {
            $n += $d;
        }

        return $n >= 1 && $n <= 99 ? $n : null;
    }

    public function stepCourtNumber(int $d): void
    {
        $n = $this->crt ? self::freeCourtNumber(collect($this->play()->boardCourts())->pluck('n')->all(), $this->crt['num'] + $d, $d) : null;
        if ($n) {
            $this->crt['num'] = $n;
        }
    }

    public function addCourt(): void
    {
        $play = $this->play();
        $n = (int) ($this->crt['num'] ?? 0);
        if ($this->attempt(fn () => $play->addCourt($n)) === false) {
            return;
        }
        $this->crt = null;
        $court = collect($play->boardCourts())->firstWhere('n', $n);
        $this->dispatch('toast', message: "Court {$n} added".match (true) {
            (bool) $court['a'] => ' · '.$play->names($court['a']['ids']).' vs '.$play->names($court['b']['ids']).' are on',
            (bool) $play->nextUpTeams() => ' · tap Fill to put the next group on',
            default => ' · fill it once 4 checked-in players are ready',
        });
    }

    /** Remove: an open court goes at once; a court with a game on asks what happens to the game. */
    public function askRemoveCourt(int $n): void
    {
        $courts = collect($this->play()->boardCourts());
        $court = $courts->firstWhere('n', $n);
        if ($court && ! $courts->contains(fn ($c) => $c['n'] !== $n && empty($c['closing']))) {
            $this->dispatch('toast', message: 'One court has to stay. To stop, end the session.');
        } elseif ($court && $court['a']) {
            $this->crt = ['step' => 'remove', 'court' => $n, 'how' => 'finish'] + ($this->crt ?? ['num' => 1]);
        } elseif ($court) {
            $this->removeCourt($n, 'now');
        }
    }

    public function removeCourt(?int $n = null, ?string $how = null): void
    {
        $play = $this->play();
        $n ??= (int) ($this->crt['court'] ?? 0);
        $how ??= ($this->crt['how'] ?? 'finish') === 'now' ? 'now' : 'finish';
        $court = collect($play->boardCourts())->firstWhere('n', $n);
        if (! $court || $this->attempt(fn () => $play->removeCourt($n, $how)) === false) {
            return;
        }
        $this->crt = null;
        if ($this->scoreCourt === $n && $how === 'now') {
            $this->scoreCourt = null;
        }
        if (($this->csub['court'] ?? null) === $n) {
            $this->csub = null;
        }
        $this->dispatch('toast', message: match (true) {
            ! $court['a'] => "Court {$n} removed · ".count($play->boardCourts()).' '.Str::plural('court', count($play->boardCourts())).' tonight',
            $how === 'finish' => "Court {$n} closes after this game · nobody new goes on it",
            default => "Court {$n} removed · game not counted · ".$play->names([...$court['a']['ids'], ...$court['b']['ids']]).' are at the front of the line',
        });
    }

    public function keepCourt(int $n): void
    {
        $play = $this->play();
        $play->keepCourt($n);
        $this->dispatch('toast', message: "Court {$n} stays open");
    }

    public function openCourtSub(int $n): void
    {
        $this->csub = ['court' => $n, 'out' => [], 'in' => [], 'after' => 'out'];
    }

    public function pickCourtSub(string $key, array|string $value): void
    {
        if ($this->csub && in_array($key, ['out', 'in', 'after'])) {
            $this->csub = [$key => $value] + ($key === 'out' ? ['in' => []] : []) + $this->csub;
        }
    }

    public function courtSub(): void
    {
        $play = $this->play();
        $cs = $this->csub ?? abort(404);
        $queue = collect($play->queue());
        $inPids = collect($cs['in'])->flatMap(fn ($id) => $queue->firstWhere('id', $id)['ids'] ?? [])->all();

        if ($this->attempt(fn () => $play->subOnCourt($cs['court'], $cs['out'], $cs['in'], $cs['after'])) !== false) {
            $this->csub = null;
            $on = $play->names($inPids);
            $off = $play->names($cs['out']);
            $many = count($cs['out']) > 1;
            $this->dispatch('toast', message: $on.($many ? ' sub in for ' : ' subs in for ')."{$off} on Court {$cs['court']} · ".match ($cs['after']) {
                'leave' => $off.($many ? ' are' : ' is').' done for the night',
                'back' => $off.($many ? ' are' : ' is').' back in line',
                default => "{$off} stepped out",
            });
        }
    }

    /** One partner of a locked pair is leaving: unlock them (same spot in line), then pick a sub for just that player. */
    public function subOne(int $entryId, int $participantId): void
    {
        $play = $this->play();
        $entry = collect($play->queue())->firstWhere('id', $entryId);
        if (! $entry || ! in_array($participantId, $entry['ids']) || $this->attempt(fn () => $play->unlockEntry($entryId)) === false) {
            return;
        }
        $this->subFor = collect($play->queue())->first(fn ($e) => $e['ids'] === [$participantId])['id'] ?? null;
        $this->dispatch('toast', message: 'Partners unlocked · who subs in for '.($play->participant($participantId)?->firstName() ?? 'Guest').'?');
    }

    public function updatedQq(): void
    {
        $this->qShow = self::QUEUE_PAGE;
    }

    public function updatedQf(): void
    {
        $this->qShow = self::QUEUE_PAGE;
    }

    public function showMore(?int $all = null): void
    {
        $this->qShow = $all ?? $this->qShow + self::QUEUE_PAGE;
    }

    /** The organizer plays too: joins their own session, already checked in. */
    public function addMe(): void
    {
        $user = auth()->user();
        abort_unless($user->isMember(), 403);
        $play = $this->play();

        $done = $this->attempt(function () use ($play, $user) {
            $play->checkIn([$play->join($user)->id]);
        });
        if ($done !== false) {
            $this->dispatch('toast', message: "You're in the queue as a player too");
        }
    }

    public function updatedWalkin(): void
    {
        $this->walkinPick = null;
        $this->walkinTried = false;
    }

    /** Add: no matching account adds a guest; with matches the organizer has to pick one or choose guest. */
    public function addWalkin(): void
    {
        $this->walkin = trim($this->walkin);
        $this->validate(['walkin' => 'required|string|max:60'], [], ['walkin' => 'name']);

        if ($this->walkinMatches()->isNotEmpty()) {
            $this->walkinTried = true;

            return;
        }
        $this->addGuestWalkin();
    }

    public function addGuestWalkin(): void
    {
        $this->walkin = trim($this->walkin);
        $this->validate(['walkin' => 'required|string|max:60'], [], ['walkin' => 'name']);
        $play = $this->play();

        if ($this->attempt(fn () => $play->addWalkin($this->walkin)) !== false) {
            $this->dispatch('toast', message: "{$this->walkin} joined the queue as a guest · checked in");
            $this->reset('walkin', 'walkinPick', 'walkinTried');
        }
    }

    public function pickWalkin(int $userId): void
    {
        $this->walkinPick = $this->walkinMatches()->firstWhere('user.id', $userId) ? $userId : null;
    }

    /** "Yes, add": the picked account joins the session, checked in. */
    public function addAccountWalkin(): void
    {
        $play = $this->play();
        $user = User::where('role', 'member')->find($this->walkinPick);
        if (! $user) {
            $this->walkinPick = null;

            return;
        }
        if ($spot = self::sessionSpot($play, $user)) {
            $this->walkinPick = null;
            $this->dispatch('toast', message: "{$user->name} is ".lcfirst($spot));

            return;
        }

        $done = $this->attempt(fn () => $play->checkIn([$play->join($user)->id]));
        if ($done !== false) {
            $this->dispatch('toast', message: "{$user->name} joined the queue · linked to their account");
            $this->reset('walkin', 'walkinPick', 'walkinTried');
        }
    }

    /** Matching accounts for the walk-in box. */
    private function walkinMatches(): Collection
    {
        return mb_strlen(trim($this->walkin)) >= 2 ? AccountMatch::find($this->walkin, 4, [], auth()->user()->club) : collect();
    }

    /** "On court 2" / "Already in the queue at #4" when this account is already in the session. */
    private static function sessionSpot(OpenPlay $play, User $user): ?string
    {
        $pid = $play->participantMap()->first(fn ($p) => $p->user_id === $user->id && ! $p->left_at)?->id;
        if (! $pid) {
            return null;
        }
        foreach ($play->boardCourts() as $c) {
            if ($c['a'] && in_array($pid, [...$c['a']['ids'], ...$c['b']['ids']])) {
                return "On court {$c['n']}";
            }
        }
        foreach ($play->queue() as $i => $e) {
            if (in_array($pid, $e['ids'])) {
                return 'Already in the queue at #'.($i + 1);
            }
        }

        return null;
    }

    public function openScore(int $n): void
    {
        $this->scoreCourt = $n;
        $this->scoreA = 0;
        $this->scoreB = 0;
    }

    public function closeScore(): void
    {
        $this->scoreCourt = null;
    }

    public function saveScore(): void
    {
        $play = $this->play();
        $n = $this->scoreCourt;
        $court = collect($play->boardCourts())->firstWhere('n', $n);

        if (! $court || ! $court['a']) {
            $this->closeScore();

            return;
        }
        if ($this->scoreA === null || $this->scoreB === null) {
            $this->dispatch('toast', message: 'Enter a score for both teams.');

            return;
        }

        $top3 = fn () => array_column(array_slice($play->standings(), 0, 3), 'participant_id');
        $before = $top3();

        $closing = ! empty(collect($play->boardCourts())->firstWhere('n', $n)['closing']);
        $game = $this->attempt(fn () => $play->recordScore($n, $this->scoreA, $this->scoreB));
        if ($game === false) {
            return;
        }

        $after = $top3();
        $this->podNew = array_values(array_diff($after, $before));
        $podium = match (true) {
            $after === $before => '',
            (bool) $this->podNew => ' · '.$play->names($this->podNew).(count($this->podNew) > 1 ? ' move' : ' moves').' onto the podium',
            default => ' · podium reshuffled',
        };

        // wins > 0 only when winners-stay kept the winning team on this court.
        $stay = (collect($play->boardCourts())->firstWhere('n', $n)['wins'] ?? 0) > 0;
        $score = max($this->scoreA, $this->scoreB).'-'.min($this->scoreA, $this->scoreB);
        $this->dispatch('toast', message: "Court {$n}: {$play->names($court[$game->winner]['ids'])} won {$score}".$podium.($stay ? ' · winners stay on' : '').($closing ? " · Court {$n} closed" : ''));

        $this->freshGame = $game->id;
        $this->closeScore();
    }

    public function render()
    {
        $play = OpenPlay::runBy(auth()->user())->status('live')->with('organizer', 'starter')->first();

        // Still live 12 hours after its end time (no scheduler run yet): end it now, as the scheduler would.
        if ($play && now()->gte($play->autoEndAt())) {
            $play->endAutomatically();
            session()->flash('toast', "{$play->title} ended automatically, ".OpenPlay::AUTO_END_HOURS." hours after its {$play->ends_at->format('g:i A')} end time");
            $this->redirectRoute('organizer.plays', navigate: true);
            $play = null;
        }

        if (! $play) {
            return view('livewire.organizer.court-board', ['play' => null]);
        }
        $play->repairQueueIds();

        $courts = $play->boardCourts();
        $queue = $play->queue();

        // Drop picks that left the queue since (seated, removed, locked elsewhere).
        $singles = collect($queue)->filter(fn ($e) => count($e['ids']) === 1)->pluck('id')->all();
        $this->selected = array_values(array_intersect($this->selected, $singles));

        $results = $play->games()->with('players')->latest('id')->take(5)->get()->map(fn ($g) => [
            'id' => $g->id,
            'court' => $g->court,
            'ago' => ($s = now()->timestamp - $g->played_at->timestamp) < 60 ? 'just now' : ago($s).' ago',
            'a' => $play->names($g->players->where('team', 'a')->pluck('participant_id')->all()),
            'b' => $play->names($g->players->where('team', 'b')->pluck('participant_id')->all()),
            'sa' => $g->score_a,
            'sb' => $g->score_b,
            'aWon' => $g->winner === 'a',
            'subs' => $g->subs ? 'Sub: '.$this->subLine($play, $g->subs) : null,
            'played' => sprintf('%02d:%02d', intdiv($p = max(0, $g->played_at->timestamp - $g->started_at->timestamp), 60), $p % 60),
            'warmup' => $g->warmup_seconds ? sprintf('%02d:%02d', intdiv($g->warmup_seconds, 60), $g->warmup_seconds % 60) : null,
        ]);

        $busy = $play->busyCourtCount();
        $free = collect($courts)->first(fn ($c) => ! $c['a']);
        $started = ago(now()->timestamp - $play->started_at->timestamp);
        $group = $play->nextGroup();
        $next = $group && $group['full'] ? $group : null;
        $ready = $play->readyIds();
        $sessionIds = $play->sessionPlayerIds();
        $user = auth()->user();
        $shareUrl = $play->shareUrl();

        // Sub panel. A single swaps with a single; a locked pair needs two players back:
        // the next two singles in line, or another locked pair.
        $subOut = $this->subFor ? collect($queue)->firstWhere('id', $this->subFor) : null;
        $this->subFor = $subOut ? $this->subFor : null;
        $isPair = fn ($e) => $e['locked'] && count($e['ids']) === 2;
        $subCands = collect();
        if ($subOut) {
            $idle = collect($queue)->map(fn ($e, $i) => $e + ['pos' => $i + 1])
                ->filter(fn ($e) => $e['id'] !== $subOut['id'] && ! in_array($e['id'], $group['used'] ?? []) && OpenPlay::isAvailable($e, $ready))->values();
            $singles = $idle->reject($isPair)->values();
            $subCands = $isPair($subOut)
                ? $singles->chunk(2)->filter(fn ($d) => $d->count() === 2)->take(3)->values()->map(fn ($d, $k) => ['es' => $d->values()->all(), 'tag' => $k ? '' : 'Next two'])
                    ->concat($idle->filter($isPair)->take(3)->map(fn ($e) => ['es' => [$e], 'tag' => 'Locked pair']))
                : $singles->take(6)->map(fn ($e, $k) => ['es' => [$e], 'tag' => $k ? '' : 'Next in line']);
            $subCands = $subCands->values();
        }

        // Everyone rotates: why these four (fewest games, new partners) and who waits a turn.
        $why = null;
        if ($next['fair'] ?? false) {
            $sg = $play->gamesTonight();
            $gs = array_map(fn ($id) => $sg[$id] ?? 0, [...$next['a']['ids'], ...$next['b']['ids']]);
            $games = min($gs) === max($gs) ? (min($gs) ? min($gs).' '.Str::plural('game', min($gs)).' each tonight' : 'first game tonight') : min($gs).' to '.max($gs).' games tonight';
            $open = collect([[$next['a'], $next['rep'][0]], [$next['b'], $next['rep'][1]]])->reject(fn ($t) => $t[0]['locked']);
            $reps = $open->filter(fn ($t) => $t[1] > 0);
            $passed = $play->passedForNext($next);
            $why = [
                'mix' => 'Fewest games first · '.$games.match (true) {
                    $open->isEmpty() => '',
                    $reps->isEmpty() => ' · '.($open->count() === 2 ? 'both teams are new partners' : $play->names($open->first()[0]['ids']).' are new partners'),
                    default => ' · '.$reps->map(fn ($t) => $play->names($t[0]['ids']).' partnered '.($t[1] === 1 ? 'once' : $t[1].' times'))->join(', ').', the fewest repeats possible',
                },
                'wait' => $passed ? $play->names($passed).(count($passed) > 1 ? ' wait' : ' waits').' a turn · they have played more games tonight or would repeat a partner' : null,
            ];
        }

        // Sub on a live court: who comes off (one player or a whole team), who goes on (checked-in queue, next in line first), and what happens to them.
        $csubCourt = $this->csub ? collect($courts)->first(fn ($c) => $c['n'] === $this->csub['court'] && $c['a']) : null;
        $this->csub = $csubCourt ? $this->csub : null;
        $csub = null;
        if ($csubCourt) {
            $outs = $this->csub['out'];
            $both = count($outs) === 2;
            $avail = collect($queue)->map(fn ($e, $i) => $e + ['pos' => $i + 1])->filter(fn ($e) => OpenPlay::isAvailable($e, $ready))->values();
            $singles = $avail->reject($isPair)->values();
            $cands = match (count($outs)) {
                1 => $singles->take(6)->map(fn ($e, $k) => ['es' => [$e], 'tag' => $k === 0 ? 'Next in line' : (in_array($e['id'], $group['used'] ?? []) ? 'Up next' : '')]),
                2 => $singles->chunk(2)->filter(fn ($d) => $d->count() === 2)->take(3)->values()->map(fn ($d, $k) => ['es' => $d->values()->all(), 'tag' => $k ? '' : 'Next two'])
                    ->concat($avail->filter($isPair)->take(3)->map(fn ($e) => ['es' => [$e], 'tag' => 'Locked pair'])),
                default => collect(),
            };
            $outTeam = $outs ? $csubCourt[in_array($outs[0], $csubCourt['a']['ids']) ? 'a' : 'b'] : null;
            $chosen = $cands->first(fn ($c) => collect($c['es'])->pluck('id')->all() == $this->csub['in']);
            $off = $both ? $play->names($outs) : ($outs ? $play->participant($outs[0])?->firstName() : null);
            $csub = [
                'court' => $csubCourt,
                'cands' => $cands->values(),
                'lockNote' => count($outs) === 1 && $outTeam['locked'] ? 'Subbing one partner unlocks '.$play->names($outTeam['ids']).' for the rest of the night.' : null,
                'off' => $off,
                'go' => $chosen ? $play->names(collect($chosen['es'])->flatMap(fn ($e) => $e['ids'])->all()).($both ? ' go on' : ' goes on').' for '.$off : null,
            ];
        }

        // The queue list: filter and page it so big nights (100+) stay usable.
        $rows = collect($queue)->map(function ($e, $i) use ($play, $ready) {
            $notHere = (bool) array_diff($e['ids'], $ready);
            $paused = ! $notHere && ($e['paused'] ?? false);

            return $e + ['pos' => $i + 1, 'notHere' => $notHere, 'paused' => $paused, 'avail' => ! $notHere && ! $paused,
                'search' => mb_strtolower(collect($e['ids'])->map(fn ($id) => ($p = $play->participant($id)) ? $p->name.' '.$p->fullName() : '')->join(' '))];
        });
        $in = fn ($r, $f) => match ($f) {
            'here' => $r['avail'], 'away' => $r['notHere'], 'out' => $r['paused'], default => true
        };
        $qf = in_array($this->qf, ['here', 'away', 'out']) ? $this->qf : 'all';
        $q = mb_strtolower(trim($this->qq));
        $hits = $rows->filter(fn ($r) => $in($r, $qf) && ($q === '' || str_contains($r['search'], $q)))->values();
        $qShow = max(self::QUEUE_PAGE, $this->qShow);

        $endsAt = $play->autoEndAt();
        $left = (int) ceil(now()->diffInSeconds($endsAt) / 60);
        $after = OpenPlay::AUTO_END_HOURS.' hours after the '.$play->ends_at->format('g:i A').' end time';

        return view('livewire.organizer.court-board', [
            // Ends on its own: a note under the title, and a warning in the last hour.
            'autoEnd' => [
                'soon' => $left <= 60,
                'note' => 'Ends on its own at '.$endsAt->format('g:i A').' '.match (true) {
                    $endsAt->isToday() => 'today',
                    $endsAt->isTomorrow() => 'tomorrow',
                    default => 'on '.$endsAt->format('D, M j'),
                }.", {$after}.",
                'warning' => 'Ends automatically in '.($left <= 1 ? 'under a minute' : $left.' minutes')." ({$after}). Every logged game and the standings are saved when it does.",
            ],
            'play' => $play,
            'courts' => $courts,
            'queue' => $queue,
            'next' => $next,
            'ready' => $ready,
            'group' => $group,
            'where' => match (true) {
                (bool) $free => "Court {$free['n']} ".($next ? 'is open' : 'is waiting'),
                ($soon = $play->longestCourtIndex()) !== null => 'Courts full · Court '.$courts[$soon]['n'].' likely next',
                default => 'Next court to finish',
            },
            'skipped' => $group ? $play->skippedForNext($group) : ['', false],
            'walkinMatches' => $this->walkinPick ? collect() : $this->walkinMatches()->map(fn ($m) => $m + ['spot' => self::sessionSpot($play, $m['user'])]),
            'walkinUser' => $this->walkinPick ? User::find($this->walkinPick) : null,
            'gamesTonight' => $play->gamesTonight(),
            'results' => $results,
            'games' => $play->games()->count(),
            'busy' => $busy,
            'inQueue' => $play->queuedPlayerCount(),
            'readyCount' => $play->readyQueueCount(),
            'here' => count(array_intersect($sessionIds, $ready)),
            'total' => count($sessionIds),
            'running' => $started,
            'started' => ($started === 'just now' ? 'Started just now' : "Started {$started} ago").match (true) {
                $play->organizer_id !== $user->id => ' · co-hosting for '.$play->organizer->name,
                $play->starter && ! $play->starter->is($user) => ' by '.$play->starter->name.' (co-host)',
                default => '',
            },
            'queueRows' => $hits->take($qShow),
            'qt' => [
                'big' => count($queue) > 8,
                'f' => $qf,
                'tabs' => collect(['all' => 'All', 'here' => 'Ready', 'away' => 'Not here', 'out' => 'Stepped out'])
                    ->map(fn ($label, $f) => [$label, $rows->filter(fn ($r) => $in($r, $f))->sum(fn ($r) => count($r['ids']))]),
                'hits' => $hits->count(),
                'rest' => max(0, $hits->count() - $qShow),
                'shown' => min($qShow, $hits->count()),
            ],
            'why' => $why,
            'courtsDialog' => $this->crt ? $this->courtsDialog($play, $courts) : null,
            'subDialog' => $csub,
            'courtSubs' => collect($courts)->mapWithKeys(fn ($c) => [$c['n'] => ($c['subs'] ?? null) ? $this->subLine($play, $c['subs']).' · '.(($s = now()->timestamp - end($c['subs'])['at']) < 60 ? 'just now' : ago($s).' ago') : null]),
            'subOut' => $subOut,
            'subCands' => $subCands,
            'canAddMe' => $user->isMember() && ! $play->participantMap()->only($sessionIds)->contains('user_id', $user->id),
            'shareUrl' => $shareUrl,
            'qr' => $shareUrl ? $this->qrSvg($shareUrl) : null,
            'poster' => $shareUrl ? [
                'title' => $play->title,
                'sub' => implode(' · ', array_filter([$play->club, $play->venue])),
                'when' => $play->whenLabel().' · '.$play->timeRange(),
                'code' => $play->share_code,
                'url' => $shareUrl,
                'shortUrl' => preg_replace('#^https?://#', '', $shareUrl),
                'rows' => $this->qrRows($shareUrl),
                'file' => 'picklex-'.strtolower($play->share_code).'-live-view-qr.png',
            ] : null,
            'scoring' => $this->scoreCourt ? collect($courts)->first(fn ($c) => $c['n'] === $this->scoreCourt && $c['a']) : null,
            'freshGame' => $this->freshGame,
            'standings' => $standings = $play->standings(),
            'standingsPage' => $this->pageOf($standings, 10, 'standingsPage'),
            'podNew' => $this->podNew,
        ]);
    }

    /** The Courts dialog: add a court by number, the list of courts tonight, or what happens to a game on a court being removed. */
    private function courtsDialog(OpenPlay $play, array $courts): ?array
    {
        $taken = array_column($courts, 'n');
        $num = $this->crt['num'] ?? 1;
        $open = collect($courts)->filter(fn ($c) => empty($c['closing']))->count();
        $remove = ($this->crt['step'] ?? null) === 'remove' ? collect($courts)->first(fn ($c) => $c['n'] === ($this->crt['court'] ?? null) && $c['a']) : null;
        if (! $remove) {
            $this->crt['step'] = 'list';
        }
        $next = $play->nextUpTeams();

        return [
            'remove' => $remove,
            'num' => $num,
            'prev' => self::freeCourtNumber($taken, $num - 1, -1),
            'next' => self::freeCourtNumber($taken, $num + 1, 1),
            'hint' => $next
                ? ($play->auto_fill ? 'Auto-fill is on, so '.$play->names($next['a']['ids']).' vs '.$play->names($next['b']['ids'])." go straight on Court {$num}." : "Court {$num} opens empty. Up next: ".$play->names($next['a']['ids']).' vs '.$play->names($next['b']['ids']).'. Tap Fill to put them on.')
                : "Court {$num} opens empty until 4 checked-in players are ready in the queue.",
            'ready' => (bool) $next,
            'busy' => collect($courts)->filter(fn ($c) => $c['a'])->count(),
            'closing' => count($courts) - $open,
            'lastOne' => $open <= 1,
            'canRemove' => fn ($c) => collect($courts)->contains(fn ($o) => $o['n'] !== $c['n'] && empty($o['closing'])),
        ];
    }

    /** "Ben in for Mika · Ana in for Rico". */
    private function subLine(OpenPlay $play, array $subs): string
    {
        $first = fn ($id) => $play->participant($id)?->firstName() ?? 'Guest';

        return collect($subs)->map(fn ($s) => $first($s['in']).' in for '.$first($s['out']))->join(' · ');
    }

    /** QR modules as '1'/'0' strings (no quiet zone) for the downloadable poster, drawn on a canvas in resources/js/share-poster.js. */
    private function qrRows(string $url): array
    {
        $qr = new QRCode(new QROptions(['addQuietzone' => false]));
        $qr->addByteSegment($url);

        return array_map(fn ($row) => implode('', array_map('intval', $row)), $qr->getQRMatrix()->getBooleanMatrix());
    }

    /** Inline SVG QR; dark modules inherit their fill from the wrapper. */
    private function qrSvg(string $url): string
    {
        $svg = (new QRCode(new QROptions([
            'outputBase64' => false, 'svgAddXmlHeader' => false, 'drawLightModules' => false,
            'connectPaths' => true, 'svgUseFillAttributes' => false, 'cssClass' => '',
        ])))->render($url);

        return str_replace('<svg ', '<svg aria-hidden="true" shape-rendering="crispEdges" ', $svg);
    }
}
