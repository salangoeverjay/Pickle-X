<?php

namespace App\Livewire\Member;

use App\Support\MemberStats;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Rankings · PickleX')]
class Rankings extends Component
{
    #[Url]
    public string $period = 'month'; // month | all

    // One table per club whose open plays you joined, plus 'all' (every club added up, all time). Empty: your home club.
    #[Url(except: '')]
    public string $club = '';

    public function render()
    {
        $user = auth()->user();
        $clubs = MemberStats::clubsOf($user);
        $scope = $this->club === 'all' || $clubs->contains('name', $this->club) ? $this->club : ($clubs->first()->name ?? 'all');
        $all = $scope === 'all';
        $period = $all ? 'all' : $this->period;
        $since = $period === 'all' ? null : now()->startOfMonth();
        $table = $all ? null : $scope;

        $rows = MemberStats::ranking($user, $since, null, $table);
        // Movement: rank now vs the same table a week ago (only players who had games then).
        $before = MemberStats::ranking($user, $since, now()->subDays(7), $table)->where('games', '>', 0)->pluck('rank', 'user.id');
        $rows->each(fn ($r) => $r->mv = isset($before[$r->user->id]) ? $before[$r->user->id] - $r->rank : 0);

        $me = $rows->firstWhere('me', true);
        $above = $me && $me->rank > 1 ? $rows[$me->rank - 2] : null;
        $per = $period === 'all' ? 'all time' : 'this month';
        $name = $all ? 'All clubs' : $scope;

        return view('livewire.member.rankings', [
            'user' => $user,
            'rows' => $rows,
            'me' => $me,
            'all' => $all,
            'scopes' => $clubs->map(fn ($c) => ['id' => $c->name, 'name' => $c->name, 'sub' => $c->name === $user->club ? 'Home club' : $c->plays.' '.Str::plural('open play', $c->plays)])
                ->push(['id' => 'all', 'name' => 'All clubs', 'sub' => 'All time'])
                ->map(fn ($x) => $x + ['on' => $x['id'] === $scope]),
            'kicker' => $name.' · '.Str::ucfirst($per),
            'scopeNote' => ($all ? 'Every PickleX player, all clubs added up, all time.' : "Players at {$scope} open plays, {$per}.")
                .($clubs->isEmpty() ? ' Join an open play and its club gets its own table here.' : ''),
            'empty' => 'No games logged '.($period === 'month' ? 'this month yet' : 'yet').($all ? '' : " at {$scope}").'. Rankings fill in after the first scores.',
            'note' => $above
                ? ($above->pts - $me->pts).' pts behind '.($above->user->shownFirst() === $user->shownFirst() ? $above->user->shownName() : $above->user->shownFirst()).' at #'.$above->rank.'. Every win is worth 13.'
                : 'Top of the table. Keep it up.',
            // Not ranked: no games in this table (a table only lists players who have some).
            'unranked' => $me ? null : [
                'games' => "0 games {$per}",
                'body' => match (true) {
                    $all => 'Your first game at any PickleX open play puts you on the table. Wins, losses and new partners all earn points.',
                    $period === 'month' => "No games at {$scope} open plays this month yet. Your next game there puts you on this table.",
                    default => "Your first game at a {$scope} open play puts you on this table. Wins, losses and new partners all earn points.",
                },
                'tips' => [
                    ['Your first game', 'Up to 15 pts', '+3 for playing, +10 for a win, +2 for a new partner'],
                    ['Top 10 · '.($all ? 'all clubs' : $per), $rows->count() >= 10 ? $rows[9]->pts.' pts' : 'Open', $rows->count() >= 10 ? 'what #10 has right now' : 'fewer than 10 players are ranked'],
                    ['Fair by design', 'No DUPR', 'rankings only count games logged at open plays'],
                ],
            ],
        ]);
    }
}
