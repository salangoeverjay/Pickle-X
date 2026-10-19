<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\Pages;
use App\Models\GamePlayer;
use App\Models\User;
use App\Support\Looks;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Admin › Sponsors: people who cover some of PickleX's costs, at gold, silver or bronze. Each tier unlocks its own look. */
#[Title('Sponsors | PickleX')]
class Sponsors extends Component
{
    use Pages;

    public string $q = '';

    /** Tier a new sponsor is added at. */
    public string $tier = 'gold';

    public function addSponsor(int $id): void
    {
        $tier = $this->validTier($this->tier);
        $user = User::where('role', 'member')->whereNull('sponsor_tier')->findOrFail($id);
        $user->forceFill(['sponsor_tier' => $tier])->save();
        $this->q = '';
        $t = Looks::TIERS[$tier];
        $this->dispatch('toast', message: "{$user->name} is a {$t} sponsor, {$t} look unlocked");
    }

    /** Move a sponsor to another tier; if they wear a sponsor look it follows the new tier. */
    public function setTier(int $id, string $tier): void
    {
        $tier = $this->validTier($tier);
        $user = User::whereNotNull('sponsor_tier')->findOrFail($id);
        if ($user->sponsor_tier === $tier) {
            return;
        }
        $follow = fn ($look) => str_starts_with((string) $look, 'sp-') ? "sp-{$tier}" : $look;
        $user->forceFill(['sponsor_tier' => $tier, 'frame' => $follow($user->frame), 'queue_bg' => $follow($user->queue_bg)])->save();
        $t = Looks::TIERS[$tier];
        $this->dispatch('toast', message: "{$user->name} moved to {$t}, {$t} sponsor look unlocked");
    }

    /** Locks the look again; if they were wearing it, their border and queue background go back to none. */
    public function removeSponsor(int $id): void
    {
        $user = User::whereNotNull('sponsor_tier')->findOrFail($id);
        $off = fn ($look) => str_starts_with((string) $look, 'sp-') ? null : $look;
        $user->forceFill(['sponsor_tier' => null, 'frame' => $off($user->frame), 'queue_bg' => $off($user->queue_bg)])->save();
        $this->dispatch('toast', message: "{$user->name} is no longer a sponsor, look locked");
    }

    private function validTier(string $tier): string
    {
        abort_unless(isset(Looks::TIERS[$tier]), 404);

        return $tier;
    }

    public function render()
    {
        $q = trim($this->q);
        $like = '%'.addcslashes(mb_strtolower($q), '%_\\').'%';
        $order = array_keys(Looks::TIERS);
        $sponsors = User::whereNotNull('sponsor_tier')->orderBy('name')->get()
            ->sortBy(fn ($u) => array_search($u->sponsor_tier, $order))->values();

        return view('livewire.admin.sponsors', [
            'sponsors' => $sponsors,
            'sponsorsPage' => $this->pageOf($sponsors, 5, 'sponsorsPage'),
            'counts' => $sponsors->countBy('sponsor_tier'),
            'results' => $q === '' ? collect() : User::where('role', 'member')->whereNull('sponsor_tier')
                ->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]))
                ->orderBy('name')->limit(5)->get(),
            'games' => fn (User $u) => GamePlayer::where('user_id', $u->id)->count(),
            'addTier' => isset(Looks::TIERS[$this->tier]) ? $this->tier : 'gold',
        ]);
    }
}
