<?php

namespace App\Livewire\Member;

use App\Models\OpenPlay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Open plays · PickleX')]
class OpenPlays extends Component
{
    // Open play whose payment QR dialog is showing.
    public ?int $payFor = null;

    public function openPay(int $id): void
    {
        abort_unless($this->joinable($id)->hasPaymentQr(), 404);
        $this->payFor = $id;
    }

    public function closePay(): void
    {
        $this->payFor = null;
    }

    /** "I've sent the payment": noted for the organizer, who still confirms it. */
    public function claimPayment(int $id): void
    {
        abort_unless($this->joinable($id)->hasPaymentQr(), 404);
        DB::table('pay_claims')->insertOrIgnore(['open_play_id' => $id, 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        $this->payFor = null;
        $this->dispatch('toast', message: 'Thanks. The organizer will mark you as paid once they see it.');
    }

    public function joinPlay(int $id): void
    {
        $play = $this->joinable($id);

        try {
            $play->join(auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: $play->isLive() ? "You're in the queue. Check in when you reach the courts." : "You're in. See you {$play->whenLabel()}.");
    }

    public function leavePlay(int $id): void
    {
        $play = $this->joinable($id);
        $play->leave(auth()->user());

        $this->dispatch('toast', message: "You left {$play->title}");
    }

    /** "I'm at the courts": only checked-in players get called. */
    public function checkInPlay(int $id): void
    {
        $play = OpenPlay::status('live')->findOrFail($id);
        $me = $play->participants()->where('user_id', auth()->id())->whereNull('left_at')->firstOrFail();
        $play->checkIn([$me->id]);

        $this->dispatch('toast', message: 'Checked in at '.now()->format('g:i A').". You're in the rotation.");
    }

    /** Back from stepping out (or being subbed out): keeps the same spot in line. */
    public function stepBackIn(int $id): void
    {
        $play = OpenPlay::status('live')->findOrFail($id);
        $status = $play->statusFor(auth()->user());
        abort_unless(($status['state'] ?? null) === 'paused', 404);
        $play->setPaused($status['entry'], false);

        $this->dispatch('toast', message: "You're back in the rotation");
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
            'paidIds' => $user->participations()->whereNull('left_at')->where('paid', true)->pluck('open_play_id')->all(),
            'claimedIds' => DB::table('pay_claims')->where('user_id', $user->id)->pluck('open_play_id')->all(),
            'payPlay' => $this->payFor ? $plays->firstWhere('id', $this->payFor) : null,
        ]);
    }
}
