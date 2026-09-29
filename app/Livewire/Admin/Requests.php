<?php

namespace App\Livewire\Admin;

use App\Models\GamePlayer;
use App\Models\OpenPlay;
use App\Models\User;
use App\Support\Emails;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Organizer requests · PickleX')]
class Requests extends Component
{
    public const TABS = ['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'];

    #[Url]
    public string $tab = 'pending';

    public function approve(int $id)
    {
        $user = $this->decide($id, 'approved');
        $this->dispatch('toast', message: "{$user->name} approved. They can host open plays now.");
    }

    public function decline(int $id)
    {
        $user = $this->decide($id, 'declined');
        $this->dispatch('toast', message: "{$user->name}'s request was declined");
    }

    public function reopen(int $id)
    {
        $user = $this->decide($id, 'pending');
        $this->dispatch('toast', message: "{$user->name} moved back to pending");
    }

    protected function decide(int $id, string $status): User
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $user = User::whereNotNull('organizer_status')->findOrFail($id);
        $changed = $user->organizer_status !== $status;
        $user->update(['organizer_status' => $status]);

        if ($changed && in_array($status, ['approved', 'declined'])) {
            Emails::send($user, $status === 'approved' ? Emails::organizerApproved($user) : Emails::organizerDeclined($user));
        }

        return $user;
    }

    public function render()
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'pending';
        }

        $counts = User::whereNotNull('organizer_status')->toBase()
            ->selectRaw('organizer_status, count(*) as n')->groupBy('organizer_status')
            ->pluck('n', 'organizer_status');

        return view('livewire.admin.requests', [
            'counts' => $counts,
            'members' => User::where('role', 'member')->count(),
            'live' => OpenPlay::status('live')->count(),
            // has_played: this account already plays (joined an open play or has games), same account.
            'requests' => User::where('organizer_status', $this->tab)
                ->withExists(['participations as has_played'])
                ->addSelect(['has_games' => GamePlayer::selectRaw('1')->whereColumn('user_id', 'users.id')->limit(1)])
                ->latest()->latest('id')->get(),
        ]);
    }
}
