<?php

namespace App\Livewire;

use App\Models\News;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/** What's new (account menu): every update for your audience. Opening it marks the unread ones read; they keep a New badge this visit. */
#[Title("What's new · PickleX")]
class WhatsNew extends Component
{
    public string $tab = 'all'; // all | feature | enhancement | fix

    #[Locked]
    public array $fresh = [];

    public function mount(): void
    {
        $user = auth()->user();
        abort_if($user->isAdmin(), 404);
        $this->fresh = News::unreadBy($user)->pluck('id')->all();
        News::markRead($user, $this->fresh);
    }

    public function render()
    {
        $all = News::for(auth()->user())->get();

        return view('livewire.whats-new', [
            'all' => $all,
            'items' => $this->tab === 'all' ? $all : $all->where('kind', $this->tab)->values(),
            'tabs' => ['all' => ['All', $all->count()]] + collect(['feature' => 'New features', 'enhancement' => 'Enhancements', 'fix' => 'Bug fixes'])
                ->map(fn ($label, $k) => [$label, $all->where('kind', $k)->count()])->all(),
        ]);
    }
}
