<?php

namespace App\Support;

use App\Models\GamePlayer;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A member's stats from logged games (game_players), plus club rankings.
 * Points: +10 per win, +3 per game, +2 per distinct partner (real users only).
 */
class MemberStats
{
    /** The member's games, newest first: {won, at, score, mates, opps}. */
    public Collection $games;

    /** Rows per real user: {user, games, wins, losses, pct, last}. */
    public Collection $partners;

    public Collection $opponents;

    public function __construct(public User $user)
    {
        $mine = GamePlayer::query()
            ->join('games', 'games.id', '=', 'game_players.game_id')
            ->where('game_players.user_id', $user->id)
            ->orderByDesc('games.played_at')->orderByDesc('games.id')
            ->get(['game_players.game_id', 'game_players.team', 'game_players.won', 'games.played_at', 'games.score_a', 'games.score_b']);

        $others = GamePlayer::with(['participant', 'user'])
            ->whereIn('game_id', GamePlayer::select('game_id')->where('user_id', $user->id))
            ->get()
            ->reject(fn ($p) => $p->user_id === $user->id)
            ->groupBy('game_id');

        $this->games = $mine->map(function ($r) use ($others) {
            $players = $others->get($r->game_id, collect());

            return (object) [
                'won' => (bool) $r->won,
                'at' => Carbon::parse($r->played_at),
                'score' => $r->team === 'a' ? "{$r->score_a}-{$r->score_b}" : "{$r->score_b}-{$r->score_a}",
                'mates' => $players->where('team', $r->team)->values(),
                'opps' => $players->where('team', '!=', $r->team)->values(),
            ];
        });

        $this->partners = $this->people('mates');
        $this->opponents = $this->people('opps');
    }

    private function people(string $side): Collection
    {
        return $this->games
            ->flatMap(fn ($g) => $g->{$side}->filter(fn ($p) => $p->user)->map(fn ($p) => [$p->user, $g]))
            ->groupBy(fn ($pair) => $pair[0]->id)
            ->map(function ($pairs) {
                $g = $pairs->count();
                $w = $pairs->filter(fn ($pair) => $pair[1]->won)->count();

                // Games are newest first, so the first pair is the last time played.
                return (object) ['user' => $pairs[0][0], 'games' => $g, 'wins' => $w, 'losses' => $g - $w, 'pct' => (int) round($w * 100 / $g), 'last' => $pairs[0][1]->at];
            })
            ->values();
    }

    public function count(): int
    {
        return $this->games->count();
    }

    public function wins(): int
    {
        return $this->games->where('won', true)->count();
    }

    public function losses(): int
    {
        return $this->count() - $this->wins();
    }

    public function rate(): int
    {
        return $this->count() ? (int) round($this->wins() * 100 / $this->count()) : 0;
    }

    public function points(): int
    {
        return $this->wins() * 10 + $this->count() * 3 + $this->partners->count() * 2;
    }

    /** Last 10 results, newest first: ['W', 'L', ...]. */
    public function form(): array
    {
        return $this->games->take(10)->map(fn ($g) => $g->won ? 'W' : 'L')->all();
    }

    /** "W3" / "L1" / "-". */
    public function streak(): string
    {
        $first = $this->games->first();

        return $first ? ($first->won ? 'W' : 'L').$this->games->takeWhile(fn ($g) => $g->won === $first->won)->count() : '-';
    }

    /** "7-3" over the last 10. */
    public function lastTen(): string
    {
        $form = $this->form();
        $w = count(array_keys($form, 'W'));

        return $w.'-'.(count($form) - $w);
    }

    /** Last 8 weeks (Monday start), oldest first: [{v, label, h}]. */
    public function weeks(): array
    {
        $start = now()->startOfWeek(CarbonInterface::MONDAY)->subWeeks(7);
        $counts = $this->games->filter(fn ($g) => $g->at >= $start)
            ->countBy(fn ($g) => $g->at->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString());
        $values = collect(range(0, 7))->map(fn ($i) => $counts[$start->copy()->addWeeks($i)->toDateString()] ?? 0);
        $max = max($values->max(), 8);

        return $values->map(fn ($v, $i) => (object) [
            'v' => $v,
            'label' => $i === 7 ? 'This wk' : $start->copy()->addWeeks($i)->format('M j'),
            'h' => max(6, (int) round($v / $max * 96)),
        ])->all();
    }

    /** Last $n games as {won, partner, opponents, score, when}. */
    public function recent(int $n = 5): Collection
    {
        $names = fn ($players) => $players->map(fn ($p) => $p->participant->firstName())->join(' & ');

        return $this->games->take($n)->map(fn ($g) => (object) [
            'won' => $g->won, 'score' => $g->score, 'when' => self::when($g->at),
            'partner' => $names($g->mates), 'opponents' => $names($g->opps),
        ]);
    }

    /** Best partner with at least 5 games together. */
    public function bestPartner(): ?object
    {
        return $this->partners->where('games', '>=', 5)->sortByDesc('pct')->first();
    }

    /** Opponent with at least 5 games against whom the member wins least. */
    public function toughestOpponent(): ?object
    {
        return $this->opponents->where('games', '>=', 5)->sortBy('pct')->first();
    }

    /** "Today", weekday within the last week, else "Sep 18". */
    public static function when(CarbonInterface $at): string
    {
        return match (true) {
            $at->isToday() => 'Today',
            $at >= today()->subDays(6) => $at->format('D'),
            default => $at->format('M j'),
        };
    }

    /**
     * Members of the viewer's club (all members when the viewer has no club) with games in the window,
     * minus those who opted out of rankings. The viewer is always included.
     *
     * @return Collection<int, object{user: User, games: int, wins: int, losses: int, pts: int, me: bool, rank: int}>
     */
    public static function ranking(User $viewer, ?CarbonInterface $since = null, ?CarbonInterface $until = null): Collection
    {
        $base = fn () => DB::table('game_players as gp')
            ->join('games as g', 'g.id', '=', 'gp.game_id')
            ->join('users as u', 'u.id', '=', 'gp.user_id')
            ->where('u.role', 'member')
            ->when($viewer->club, fn ($q, $club) => $q->where('u.club', $club))
            ->when($since, fn ($q) => $q->where('g.played_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('g.played_at', '<=', $until))
            ->groupBy('gp.user_id');

        $totals = $base()->selectRaw('gp.user_id, count(*) as games, sum(gp.won) as wins')->get()->keyBy('user_id');
        $partners = $base()
            ->join('game_players as p', fn ($j) => $j->on('p.game_id', '=', 'gp.game_id')->on('p.team', '=', 'gp.team')->on('p.user_id', '!=', 'gp.user_id'))
            ->selectRaw('gp.user_id, count(distinct p.user_id) as n')
            ->pluck('n', 'user_id');

        return User::whereIn('id', $totals->keys()->push($viewer->id))->get()
            ->filter(fn ($u) => $u->is($viewer) || $u->pref('privacy.ranked'))
            ->map(function ($u) use ($totals, $partners, $viewer) {
                $g = (int) ($totals[$u->id]->games ?? 0);
                $w = (int) ($totals[$u->id]->wins ?? 0);

                return (object) ['user' => $u, 'games' => $g, 'wins' => $w, 'losses' => $g - $w, 'pts' => $w * 10 + $g * 3 + (int) ($partners[$u->id] ?? 0) * 2, 'me' => $u->is($viewer)];
            })
            ->sort(fn ($a, $b) => [$b->pts, $b->wins] <=> [$a->pts, $a->wins])
            ->values()
            ->each(fn ($r, $i) => $r->rank = $i + 1);
    }

    public static function rankOf(User $viewer, ?CarbonInterface $since = null): int
    {
        return self::ranking($viewer, $since)->firstWhere('me', true)->rank;
    }
}
