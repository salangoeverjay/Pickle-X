<?php

namespace App\Livewire\Member;

use App\Models\OpenPlay;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Open plays · PickleX')]
class OpenPlays extends Component
{
    public function join(int $id): void
    {
        $play = $this->joinable($id);

        try {
            $play->join(auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: $play->isLive() ? "You're in — added to the queue" : "You're in! See you {$play->whenLabel()}");
    }

    public function leave(int $id): void
    {
        $play = $this->joinable($id);
        $play->leave(auth()->user());

        $this->dispatch('toast', message: "You left {$play->title}");
    }

    /** Members only act on public sessions that haven't ended. */
    private function joinable(int $id): OpenPlay
    {
        return OpenPlay::public()->whereIn('status', ['live', 'upcoming'])->findOrFail($id);
    }

    public function render()
    {
        $user = auth()->user();
        $joined = $user->participations()->whereNull('left_at')->pluck('open_play_id')->all();

        $plays = OpenPlay::public()->whereIn('status', ['live', 'upcoming'])
            ->withCount('activeParticipants')->orderBy('starts_at')->get();

        // "My status": the first live session (public or not) the member is standing in.
        $my = null;
        foreach (OpenPlay::status('live')->whereIn('id', $joined)->get() as $play) {
            if ($status = $play->statusFor($user)) {
                $index = $status['state'] === 'court' ? $status['index'] : $play->longestCourtIndex();
                $my = [
                    'play' => $play,
                    'status' => $status,
                    'court' => $index === null ? null : $play->boardCourts()[$index],
                    'index' => $index,
                ];
                break;
            }
        }

        return view('livewire.member.open-plays', [
            'my' => $my,
            'live' => $plays->where('status', 'live'),
            'upcoming' => $plays->where('status', 'upcoming'),
            'joined' => $joined,
        ]);
    }
}
