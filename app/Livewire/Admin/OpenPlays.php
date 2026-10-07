<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\Pages;
use App\Models\OpenPlay;
use App\Support\Emails;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin › Open plays: concluded open plays, and deleting a test or duplicate one.
 * Deleting takes its games (and so everyone's rankings, played-with lists and stats from it) with it;
 * the host is emailed the reason, players aren't.
 */
#[Title('Open plays · PickleX')]
class OpenPlays extends Component
{
    use Pages;

    /** [label, hint, "because it was ..." for the email]. The hint's {host} is the host's first name. */
    public const REASONS = [
        'test' => ['It was a test', 'Made to try PickleX out', 'a test'],
        'dup' => ["It's a duplicate", 'The same session was logged twice', 'a duplicate'],
        'other' => ['Something else', '{host} can reply to ask', null],
    ];

    #[Url(except: 'all')]
    public string $tab = 'all'; // all | test

    public string $q = '';

    public ?int $deleting = null;

    public string $why = 'test';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public static function looksTest(string $title): bool
    {
        return (bool) preg_match('/\b(test|testing|ignore|trial|dummy|sample)\b/i', $title);
    }

    public function askDelete(int $id): void
    {
        $play = $this->concluded($id);
        $this->deleting = $play->id;
        $this->why = self::looksTest($play->title) ? 'test' : 'dup';
    }

    public function cancelDelete(): void
    {
        $this->reset('deleting', 'why');
    }

    public function pickWhy(string $why): void
    {
        if (isset(self::REASONS[$why])) {
            $this->why = $why;
        }
    }

    public function delete(): void
    {
        $play = $this->concluded($this->deleting);
        $play->loadCount('games');
        $players = count($play->standings());
        $host = $play->organizer;
        $mail = $host ? Emails::playDeleted($play, $this->why, $play->games_count, $players) : null;

        DB::transaction(function () use ($play, $host, $players) {
            DB::table('deleted_open_plays')->insert([
                'title' => $play->title, 'club' => $play->club, 'host' => $host?->name, 'starts_at' => $play->starts_at,
                'games' => $play->games_count, 'players' => $players, 'reason' => $this->why,
                'deleted_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $play->delete(); // participants, games and game_players cascade
        });
        if ($mail) {
            Emails::send($host, $mail);
        }

        $this->cancelDelete();
        $this->dispatch('toast', message: "{$play->title} deleted · {$play->games_count} ".Str::plural('game', $play->games_count)." came off {$players} ".($players === 1 ? "player's" : "players'").' stats'.($host ? " · we emailed {$host->firstName()}" : ''));
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'q'])) {
            $this->resetPage('playsPage');
        }
    }

    public function render()
    {
        $all = OpenPlay::status('done')->with('organizer')->withCount('games')
            ->addSelect(['players_count' => $this->playersQuery()])
            ->orderByDesc('starts_at')->get();
        $q = Str::lower(trim($this->q));
        $rows = $all->filter(fn ($p) => $this->tab !== 'test' || self::looksTest($p->title))
            ->filter(fn ($p) => $q === '' || Str::contains(Str::lower($p->title.' '.$p->club.' '.$p->organizer?->name), $q))
            ->values();
        $tests = $all->filter(fn ($p) => self::looksTest($p->title))->count();
        $done = DB::table('deleted_open_plays')->latest('id')->get();
        $play = $this->deleting ? $this->concluded($this->deleting)->loadCount('games') : null;

        return view('livewire.admin.open-plays', [
            'rows' => $rows,
            'rowsPage' => $this->pageOf($rows, 10, 'playsPage'),
            'total' => $all->count(),
            'tabs' => ['all' => ['All', $all->count()], 'test' => ['Look like tests', $tests]],
            'tiles' => [['Concluded', $all->count(), false], ['Games logged', $all->sum('games_count'), false], ['Look like tests', $tests, $tests > 0], ['Deleted', $done->count(), false]],
            // ponytail: the latest 10 deletions, page them if admins ever delete more than that.
            'done' => $done->take(10),
            'play' => $play,
            'people' => $play ? $this->people($play) : collect(),
        ]);
    }

    /** Who played in it, most games first: [participant, w, l]. */
    private function people(OpenPlay $play)
    {
        $by = $play->participantMap();

        return collect($play->standings())
            ->sortBy([fn ($a, $b) => $b['g'] <=> $a['g'], fn ($a, $b) => $b['w'] <=> $a['w']])
            ->map(fn ($r) => [$by[$r['participant_id']] ?? null, $r['w'], $r['l']])
            ->filter(fn ($r) => $r[0])
            ->values();
    }

    /** Distinct players with a logged game in the open play (a subquery on open_plays). */
    private function playersQuery(): Builder
    {
        return DB::table('game_players')->join('games', 'games.id', '=', 'game_players.game_id')
            ->whereColumn('games.open_play_id', 'open_plays.id')
            ->selectRaw('count(distinct game_players.participant_id) as players_count');
    }

    private function concluded(?int $id): OpenPlay
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return OpenPlay::status('done')->with('organizer')->findOrFail($id);
    }
}
