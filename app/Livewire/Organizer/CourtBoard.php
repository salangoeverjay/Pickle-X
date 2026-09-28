<?php

namespace App\Livewire\Organizer;

use App\Models\OpenPlay;
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

    /** Game saved in this request, so its result row can animate in. */
    protected ?int $freshGame = null;

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

    public function end()
    {
        $play = $this->play();
        $games = $play->games()->count();
        $play->end();

        session()->flash('toast', "Session ended · {$games} games logged");

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
            : 'Need 4 players in the queue to fill a court');
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

        $game = $this->attempt(fn () => $play->recordScore($n, $this->scoreA, $this->scoreB));
        if ($game === false) {
            return;
        }

        // wins > 0 only when winners-stay kept the winning team on this court.
        $stay = (collect($play->boardCourts())->firstWhere('n', $n)['wins'] ?? 0) > 0;
        $score = max($this->scoreA, $this->scoreB).'–'.min($this->scoreA, $this->scoreB);
        $this->dispatch('toast', message: "Court {$n}: {$play->names($court[$game->winner]['ids'])} won {$score}".($stay ? ' · winners stay on' : ''));

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

        return view('livewire.organizer.court-board', [
            'play' => $play,
            'courts' => $courts,
            'queue' => $queue,
            'next' => $play->nextUpTeams(),
            'where' => $free ? "Court {$free['n']} is open" : 'Next court to finish',
            'gamesTonight' => $play->gamesTonight(),
            'results' => $results,
            'games' => $play->games()->count(),
            'busy' => $busy,
            'inQueue' => $play->queuedPlayerCount(),
            'started' => $started === 'just now' ? 'Started just now' : "Started {$started} ago",
            'scoring' => $this->scoreCourt ? collect($courts)->first(fn ($c) => $c['n'] === $this->scoreCourt && $c['a']) : null,
            'freshGame' => $this->freshGame,
        ]);
    }
}
