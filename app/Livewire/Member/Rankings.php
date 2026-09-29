<?php

namespace App\Livewire\Member;

use App\Support\MemberStats;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Rankings · PickleX')]
class Rankings extends Component
{
    #[Url]
    public string $period = 'month'; // month | all

    public function render()
    {
        $user = auth()->user();
        $since = $this->period === 'all' ? null : now()->startOfMonth();

        $rows = MemberStats::ranking($user, $since);
        // Movement: rank now vs the same table a week ago (only players who had games then).
        $before = MemberStats::ranking($user, $since, now()->subDays(7))->where('games', '>', 0)->pluck('rank', 'user.id');
        $rows->each(fn ($r) => $r->mv = isset($before[$r->user->id]) ? $before[$r->user->id] - $r->rank : 0);

        $me = $rows->firstWhere('me', true);
        $above = $me && $me->rank > 1 ? $rows[$me->rank - 2] : null;

        return view('livewire.member.rankings', [
            'user' => $user,
            'rows' => $rows,
            'me' => $me,
            'note' => match (true) {
                ! $me => ($this->period === 'all' ? 'Play your first game at an open play to get on the board.' : 'No games this month yet. Play at an open play to get on the board.').' Every win is worth 13.',
                (bool) $above => ($above->pts - $me->pts).' pts behind '.$above->user->shownFirst().' at #'.$above->rank.'. Every win is worth 13.',
                default => 'Top of the table. Keep it up.',
            },
        ]);
    }
}
