<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\Pages;
use App\Models\GamePlayer;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Admin › Developers: accounts marked as developer unlock the exclusive Developer look. */
#[Title('Developers | PickleX')]
class Developers extends Component
{
    use Pages;

    public string $q = '';

    public function makeDeveloper(int $id): void
    {
        $user = User::where('role', 'member')->where('is_developer', false)->findOrFail($id);
        $user->forceFill(['is_developer' => true])->save();
        $this->q = '';
        $this->dispatch('toast', message: "{$user->name} is a developer, Developer look unlocked");
    }

    /** Locks the look again; if they were wearing it, their border and queue background go back to none. */
    public function removeDeveloper(int $id): void
    {
        $user = User::where('is_developer', true)->findOrFail($id);
        $user->forceFill([
            'is_developer' => false,
            'frame' => $user->frame === 'dev' ? null : $user->frame,
            'queue_bg' => $user->queue_bg === 'dev' ? null : $user->queue_bg,
        ])->save();
        $this->dispatch('toast', message: "{$user->name} is no longer a developer, look locked");
    }

    public function render()
    {
        $q = trim($this->q);
        $like = '%'.addcslashes(mb_strtolower($q), '%_\\').'%';

        return view('livewire.admin.developers', [
            'devs' => $devs = User::where('is_developer', true)->orderBy('name')->get(),
            'devsPage' => $this->pageOf($devs, 5, 'devsPage'),
            'results' => $q === '' ? collect() : User::where('role', 'member')->where('is_developer', false)
                ->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]))
                ->orderBy('name')->limit(5)->get(),
            'games' => fn (User $u) => GamePlayer::where('user_id', $u->id)->count(),
        ]);
    }
}
