<?php

namespace App\Livewire;

use App\Livewire\Concerns\Pages;
use App\Models\OpenPlay;
use App\Support\Results;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Final results sheet for a concluded open play. Lives on the member and organizer
 * open plays pages; ?results={id} opens it (links in recap emails use this).
 */
class PastResults extends Component
{
    use Pages;

    #[Url(as: 'results', except: null)]
    public ?int $playId = null;

    #[On('show-results')]
    public function show(int $id): void
    {
        $this->playId = $id;
        $this->resetPage('resultsPage');
    }

    public function close(): void
    {
        $this->playId = null;
    }

    /** Organizers of the play, anyone who was in it, and admins can see its results. */
    protected function visible(?OpenPlay $play): bool
    {
        $user = auth()->user();

        return $play && $play->status === 'done' && (
            $play->organizer_id === $user->id || $user->isAdmin()
            || $play->participants()->where('user_id', $user->id)->exists()
        );
    }

    public function render()
    {
        $play = $this->playId ? OpenPlay::find($this->playId) : null;
        if ($this->playId && ! $this->visible($play)) {
            $this->playId = null;
            $play = null;
        }

        $results = $play ? new Results($play) : null;
        $pid = $play?->participants()->where('user_id', auth()->id())->value('id');

        return view('livewire.past-results', [
            'play' => $play,
            'results' => $results,
            'resultsPage' => $results ? $this->pageOf($results->rows, 10, 'resultsPage') : null,
            'pid' => $pid,
            'mine' => $results?->mine($pid),
        ]);
    }
}
