<?php

namespace App\Livewire\Concerns;

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\Emails;
use App\Support\UpNext;
use InvalidArgumentException;

/** Start an open play you host or co-host: the queue fills, players who want it get the "it's live" email, and the court board opens. */
trait StartsSessions
{
    public function startSession(int $id)
    {
        $play = OpenPlay::findOrFail($id);
        abort_unless($play->isRunBy(auth()->user()), 403);

        if (OpenPlay::runBy(auth()->user())->status('live')->exists()) {
            $this->dispatch('toast', message: 'End the current live session first');

            return;
        }

        try {
            $play->start(auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        Emails::notify($play->activeParticipants()->where('user_id', '!=', $play->organizer_id)->with('user')->get()->pluck('user'), 'liveStart', fn (User $u) => Emails::liveStarted($play, $u));
        UpNext::notify($play);
        session()->flash('toast', "{$play->title} is live. Courts filled from the queue.");

        return $this->redirectRoute('organizer.board', navigate: true);
    }
}
