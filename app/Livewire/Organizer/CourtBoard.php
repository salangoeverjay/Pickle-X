<?php

namespace App\Livewire\Organizer;

use App\Models\OpenPlay;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Court board · PickleX')]
class CourtBoard extends Component
{
    /** Mobile only: which column shows (courts|queue). */
    public string $tab = 'courts';

    /** Queue entry ids picked to lock as partners (max 2, oldest drops). */
    public array $selected = [];

    public string $walkin = '';

    /** Court whose score sheet is open; null when closed. Null scores = blank input. */
    public ?int $scoreCourt = null;

    public ?int $scoreA = 0;

    public ?int $scoreB = 0;

    /** Up-next queue entry whose sub panel is open. */
    public ?int $subFor = null;

    /** Game saved in this request, so its result row can animate in. */
    protected ?int $freshGame = null;

    /** Participant ids that just moved onto the podium (animated once). */
    protected array $podNew = [];

    /** The organizer's live session. Querying through their own plays is the authorization. */
    private function play(): OpenPlay
    {
        $play = auth()->user()->openPlays()->status('live')->first();
        abort_unless($play, 403);

        return $play;
    }

    /** Run a board write; model rule violations come back as a toast. */
    private function attempt(callable $fn): mixed
    {
        try {
            return $fn();
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
            $this->dispatch('toast', message: $paused ? "{$names} stepped out · keeps their spot" : "{$names} is back in the rotation");
        }
    }

    public function subIn(int $outId, int $inId): void
    {
        $play = $this->play();
        $queue = collect($play->queue());
        [$out, $in] = [$queue->firstWhere('id', $outId), $queue->firstWhere('id', $inId)];
        $this->subFor = null;
        $this->selected = [];

        if ($out && $in && $this->attempt(fn () => $play->subIn($outId, $inId)) !== false) {
            $first = $play->participant($out['ids'][0])?->firstName() ?? 'Guest';
            $this->dispatch('toast', message: "{$play->names($in['ids'])} subs in for {$play->names($out['ids'])} · {$first} keeps their spot");
        }
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

    public function addWalkin(): void
    {
        $this->walkin = trim($this->walkin);
        $this->validate(['walkin' => 'required|string|max:60'], [], ['walkin' => 'name']);

        $play = $this->play();

        if ($this->attempt(fn () => $play->addWalkin($this->walkin)) !== false) {
            $this->dispatch('toast', message: "{$this->walkin} joined the queue as a walk-in");
            $this->walkin = '';
        }
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
        $this->dispatch('toast', message: "Court {$n}: {$play->names($court[$game->winner]['ids'])} won {$score}".$podium.($stay ? ' · winners stay on' : ''));

        $this->freshGame = $game->id;
        $this->closeScore();
    }

    public function render()
    {
        $play = auth()->user()->openPlays()->status('live')->first();

        if (! $play) {
            return view('livewire.organizer.court-board', ['play' => null]);
        }

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
        ]);

        $busy = $play->busyCourtCount();
        $free = collect($courts)->first(fn ($c) => ! $c['a']);
        $started = ago(now()->timestamp - $play->started_at->timestamp);
        $next = $play->nextUpTeams();
        $ready = $play->readyIds();
        $sessionIds = $play->sessionPlayerIds();
        $user = auth()->user();
        $shareUrl = $play->shareUrl();

        // Sub panel: available waiting entries of the same shape, not already up next.
        $subOut = $this->subFor ? collect($queue)->firstWhere('id', $this->subFor) : null;
        $this->subFor = $subOut ? $this->subFor : null;
        $isPair = fn ($e) => $e['locked'] && count($e['ids']) === 2;
        $subCands = $subOut ? collect($queue)
            ->map(fn ($e, $i) => $e + ['pos' => $i + 1])
            ->filter(fn ($e) => $e['id'] !== $subOut['id'] && ! in_array($e['id'], $next['used'] ?? [])
                && OpenPlay::isAvailable($e, $ready) && $isPair($e) === $isPair($subOut))
            ->take(6)->values() : collect();

        return view('livewire.organizer.court-board', [
            'play' => $play,
            'courts' => $courts,
            'queue' => $queue,
            'next' => $next,
            'ready' => $ready,
            'where' => $free ? "Court {$free['n']} is open" : 'Next court to finish',
            'gamesTonight' => $play->gamesTonight(),
            'results' => $results,
            'games' => $play->games()->count(),
            'busy' => $busy,
            'inQueue' => $play->queuedPlayerCount(),
            'readyCount' => $play->readyQueueCount(),
            'here' => count(array_intersect($sessionIds, $ready)),
            'total' => count($sessionIds),
            'running' => $started,
            'started' => $started === 'just now' ? 'Started just now' : "Started {$started} ago",
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
            'standings' => $play->standings(),
            'podNew' => $this->podNew,
        ]);
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
