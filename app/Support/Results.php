<?php

namespace App\Support;

use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Final results for concluded open plays: standings, game log and highlights, plus
 * each member's and organizer's history. Everything is derived from logged games.
 */
class Results
{
    public const MEDALS = [['#F2C94C', 'Gold'], ['#D5DAD2', 'Silver'], ['#E3A57A', 'Bronze']];

    /** Standings rows (see OpenPlay::standings()). */
    public array $rows;

    /** Games, newest first: {id, court, a, b (participant ids), sa, sb, aWon}. */
    public Collection $log;

    public function __construct(public OpenPlay $play)
    {
        $this->rows = $play->standings();
        $this->log = $play->games()->with('players')->latest('played_at')->latest('id')->get()->map(fn ($g) => [
            'id' => $g->id,
            'court' => $g->court,
            'a' => $g->players->where('team', 'a')->pluck('participant_id')->all(),
            'b' => $g->players->where('team', 'b')->pluck('participant_id')->all(),
            'sa' => $g->score_a,
            'sb' => $g->score_b,
            'aWon' => $g->winner === 'a',
        ]);
    }

    /** People who played or checked in. */
    public function players(): int
    {
        return max(count($this->rows), $this->play->participants()->whereNotNull('checked_in_at')->count());
    }

    /** "11-9": winner's score first. */
    public static function score(array $g): string
    {
        return max($g['sa'], $g['sb']).'-'.min($g['sa'], $g['sb']);
    }

    public function closest(): ?array
    {
        return $this->log->sortBy(fn ($g) => abs($g['sa'] - $g['sb']))->first();
    }

    public function biggest(): ?array
    {
        return $this->log->sortByDesc(fn ($g) => abs($g['sa'] - $g['sb']))->first();
    }

    /** Winners' first names, "Kai & Nico". */
    public function winners(array $g): string
    {
        return $this->play->names($g['aWon'] ? $g['a'] : $g['b']);
    }

    /** [label, value] cards: most wins, best point difference, biggest win. */
    public function highlights(): array
    {
        $first = fn ($row) => $this->play->participant($row['participant_id'])?->firstName();
        $best = collect($this->rows)->sortByDesc('diff')->first();
        $big = $this->biggest();

        return array_values(array_filter([
            $this->rows ? ['Most wins', $first($this->rows[0]).', '.$this->rows[0]['w'].'-'.$this->rows[0]['l'], 'trophy'] : null,
            $best ? ['Best point difference', $first($best).', '.self::signed($best['diff']), 'trend'] : null,
            $big ? ['Biggest win', self::score($big).', '.$this->winners($big), 'bolt'] : null,
        ]));
    }

    /** The viewer's row and games, or null if they didn't play. */
    public function mine(?int $participantId): ?array
    {
        $row = collect($this->rows)->firstWhere('participant_id', $participantId);
        if (! $row) {
            return null;
        }

        $games = $this->log->filter(fn ($g) => in_array($participantId, [...$g['a'], ...$g['b']]))->map(function ($g) use ($participantId) {
            $onA = in_array($participantId, $g['a']);
            $won = $g['aWon'] === $onA;

            return [
                'won' => $won,
                'with' => $this->play->names(array_values(array_diff($onA ? $g['a'] : $g['b'], [$participantId]))) ?: 'Solo',
                'vs' => $this->play->names($onA ? $g['b'] : $g['a']),
                'score' => ($onA ? $g['sa'] : $g['sb']).'-'.($onA ? $g['sb'] : $g['sa']),
            ];
        })->values();

        return $row + ['of' => count($this->rows), 'games' => $games];
    }

    public static function signed(int $n): string
    {
        return ($n > 0 ? '+' : ($n < 0 ? '−' : '')).abs($n);
    }

    public static function ordinal(int $n): string
    {
        $t = $n % 100;

        return $n.($t >= 11 && $t <= 13 ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th'));
    }

    /**
     * A member's concluded open plays where they logged games, newest first:
     * [{play, row (standings + of)}], plus totals.
     */
    public static function memberHistory(User $user, int $limit = 20): array
    {
        $plays = OpenPlay::status('done')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants' => fn ($q) => $q->where('user_id', $user->id)])
            ->latest('starts_at')->limit($limit)->get();

        $rows = $plays->map(function (OpenPlay $play) {
            $standings = $play->standings();
            $row = collect($standings)->firstWhere('participant_id', $play->participants->first()?->id);

            return $row ? ['play' => $play, 'row' => $row + ['of' => count($standings)]] : null;
        })->filter()->values();

        $w = $rows->sum('row.w');
        $l = $rows->sum('row.l');
        $medals = collect([1, 2, 3])->map(fn ($r) => $rows->where('row.rank', $r)->count())->all();

        return [
            'rows' => $rows,
            'w' => $w,
            'l' => $l,
            'medals' => $medals,
            'best' => $rows->sortBy('row.rank')->first(),
        ];
    }
}
