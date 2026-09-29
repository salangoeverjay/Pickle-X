<?php

namespace App\Livewire\Member;

use App\Support\MemberStats;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Played with · PickleX')]
class PlayedWith extends Component
{
    #[Url]
    public string $tab = 'partners'; // partners | opponents

    #[Url]
    public string $sort = 'games'; // games | rate

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function render()
    {
        $stats = new MemberStats(auth()->user());
        $q = Str::lower(trim($this->search));

        $rows = ($this->tab === 'opponents' ? $stats->opponents : $stats->partners)
            ->filter(fn ($r) => $q === '' || Str::contains(Str::lower($r->user->name.' '.$r->user->shownName()), $q))
            ->sortBy($this->sort === 'rate' ? [['pct', 'desc'], ['games', 'desc']] : [['games', 'desc'], ['pct', 'desc']])
            ->values();

        // Nothing logged yet: a guide to how the page fills up replaces the empty tiles, tabs and table.
        $guide = $stats->partners->isEmpty() && $stats->opponents->isEmpty()
            ? ['joined' => auth()->user()->participations()->whereNull('left_at')->whereHas('openPlay', fn ($q) => $q->where('status', '!=', 'done'))->with('openPlay')->first()?->openPlay]
            : null;

        return view('livewire.member.played-with', [
            'guide' => $guide,
            'stats' => $stats,
            'rows' => $rows,
            'best' => $stats->bestPartner(),
            'tough' => $stats->toughestOpponent(),
        ]);
    }
}
