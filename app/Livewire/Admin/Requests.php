<?php

namespace App\Livewire\Admin;

use App\Models\OpenPlay;
use App\Models\User;
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
        $this->dispatch('toast', message: "{$user->name} approved — they can host open plays now");
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

        $user = User::where('role', 'organizer')->findOrFail($id);
        $user->update(['organizer_status' => $status]);

        return $user;
    }

    public function render()
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'pending';
        }

        $counts = User::where('role', 'organizer')->toBase()
            ->selectRaw('organizer_status, count(*) as n')->groupBy('organizer_status')
            ->pluck('n', 'organizer_status');

        return view('livewire.admin.requests', [
            'counts' => $counts,
            'members' => User::where('role', 'member')->count(),
            'live' => OpenPlay::status('live')->count(),
            'requests' => User::where('role', 'organizer')->where('organizer_status', $this->tab)->latest()->latest('id')->get(),
        ]);
    }
}
