<?php

namespace Tests\Feature;

use App\Livewire\Organizer\CourtBoard;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourtBoardScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    /** A live play with $players queued; start() auto-fills the courts. */
    private function livePlay(int $players = 9, array $attrs = []): OpenPlay
    {
        $this->org = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved']);
        $play = OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 2, 'cap' => 24,
        ]);
        User::factory($players)->create()->each(fn ($u) => $play->join($u));
        $play->start();

        return $play;
    }

    public function test_page_renders_for_organizers_only(): void
    {
        $this->livePlay();

        $this->actingAs($this->org)->get(route('organizer.board'))->assertOk()->assertSee('Monday Night')->assertSee('Enter score');
        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('organizer.board'))->assertForbidden();
    }

    public function test_empty_state_without_a_live_session(): void
    {
        $org = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved']);

        $this->actingAs($org)->get(route('organizer.board'))->assertOk()->assertSee('No live session')->assertSee('Go to open plays');
    }

    public function test_score_entry_validates_and_records(): void
    {
        $play = $this->livePlay();
        $names = $play->names($play->boardCourts()[0]['a']['ids']);

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openScore', 1)
            ->assertSee('Enter the score')
            ->set('scoreA', 11)->set('scoreB', 11)
            ->call('saveScore')
            ->assertDispatched('toast', message: 'Race to 11: exactly one team must reach it.')
            ->assertSet('scoreCourt', 1);
        $this->assertSame(0, $play->games()->count());

        $board->set('scoreB', 5)->call('saveScore')
            ->assertDispatched('toast', message: "Court 1: {$names} won 11–5")
            ->assertSet('scoreCourt', null)
            ->assertSee('Latest results');
        $this->assertSame(1, $play->games()->count());
    }

    public function test_winners_stay_on_toast(): void
    {
        $this->livePlay(10, ['courts' => 1, 'rotation' => 'winners']);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openScore', 1)->set('scoreA', 3)->set('scoreB', 11)->call('saveScore')
            ->assertDispatched('toast', fn ($name, $params) => str_ends_with($params['message'], 'won 11–3 · winners stay on'));
    }

    public function test_fill_court_and_auto_fill_switch(): void
    {
        $play = $this->livePlay();

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)->call('toggleAutoFill');
        $this->assertFalse($play->fresh()->auto_fill);

        $play->refresh()->recordScore(1, 11, 4);
        $this->assertSame(1, $play->busyCourtCount());

        $next = $play->nextUpTeams();
        $board->call('fillCourt', 1)
            ->assertDispatched('toast', message: "Court 1: {$play->names($next['a']['ids'])} vs {$play->names($next['b']['ids'])}");
        $this->assertSame(2, $play->fresh()->busyCourtCount());

        $board->call('fillCourt', 1)->assertDispatched('toast', message: 'Need 4 players in the queue to fill a court');
    }

    public function test_lock_unlock_and_remove_queue_entries(): void
    {
        $play = $this->livePlay(11); // 3 waiting after courts fill
        [$e1, $e2, $e3] = collect($play->queue())->pluck('id')->all();

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('toggleSelect', $e1)->call('toggleSelect', $e2)->call('toggleSelect', $e3)
            ->assertSet('selected', [$e2, $e3])
            ->call('lockPartners')
            ->assertSet('selected', []);

        $locked = collect($play->fresh()->queue())->firstWhere('locked', true);
        $this->assertNotNull($locked);
        $board->assertDispatched('toast', message: $play->names($locked['ids']).' locked as partners');

        $board->call('unlockEntry', $locked['id'])->assertDispatched('toast', message: 'Partners unlocked');
        $this->assertNull(collect($play->fresh()->queue())->firstWhere('locked', true));

        $first = $play->fresh()->queue()[0];
        $board->call('removeEntry', $first['id'])->assertDispatched('toast', message: $play->names($first['ids']).' left the queue');
        $this->assertSame(2, $play->fresh()->queuedPlayerCount());
    }

    public function test_walkins_join_the_queue(): void
    {
        $play = $this->livePlay();

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->set('walkin', '   ')->call('addWalkin')->assertHasErrors('walkin')
            ->set('walkin', str_repeat('x', 61))->call('addWalkin')->assertHasErrors('walkin')
            ->set('walkin', ' Tito Boy ')->call('addWalkin')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Tito Boy joined the queue as a walk-in')
            ->assertSet('walkin', '')
            ->assertSee('Tito Boy');

        $this->assertSame(2, $play->fresh()->queuedPlayerCount());
    }

    public function test_end_session_redirects_with_toast(): void
    {
        $play = $this->livePlay();
        $play->recordScore(1, 11, 2);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('end')
            ->assertRedirect(route('organizer.plays'));

        $this->assertSame('done', $play->fresh()->status);
        $this->assertSame('Session ended · 1 games logged', session('toast'));
    }

    public function test_actions_are_forbidden_without_own_live_session(): void
    {
        $this->livePlay();
        $other = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved']);

        Livewire::actingAs($other)->test(CourtBoard::class)->call('fillCourt', 1)->assertForbidden();
    }
}
