<?php

namespace Tests\Feature;

use App\Livewire\Organizer\CourtBoard;
use App\Livewire\Organizer\OpenPlays;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarmupTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    private function livePlay(array $attrs = []): OpenPlay
    {
        $this->org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 1, 'cap' => 24,
        ]);
        User::factory(8)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();

        return $play->fresh();
    }

    public function test_a_new_foursome_warms_up_before_the_game_clock_starts(): void
    {
        $this->freezeSecond();
        $play = $this->livePlay();
        $court = $play->boardCourts()[0];
        $this->assertSame(now()->timestamp + 180, $court['started_at']);
        $this->assertSame(now()->timestamp, $court['w0']);
        $this->assertTrue(OpenPlay::isWarming($court));

        // Two minutes in, the organizer skips the rest: the clock starts now and the game records 2:00 of warmup.
        $this->travel(2)->minutes();
        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Warming up')->assertSee('Start game now')
            ->call('startNow', 1)->assertDispatched('toast', message: 'Court 1: game on, warmed up for 02:00')
            ->assertSee('Enter score')->assertDontSee('Start game now');

        $this->travel(10)->minutes();
        $game = $play->fresh()->recordScore(1, 11, 5);
        $this->assertSame(120, $game->warmup_seconds);
        $this->assertSame(600, $game->played_at->timestamp - $game->started_at->timestamp);
        Livewire::actingAs($this->org)->test(CourtBoard::class)->assertSee('played 10:00, warmup 02:00');
    }

    public function test_winners_who_stay_on_skip_it_and_changes_apply_from_the_next_game(): void
    {
        $this->freezeSecond();
        $play = $this->livePlay(['rotation' => 'winners']);
        $play->recordScore(1, 11, 4);
        $court = $play->fresh()->boardCourts()[0];
        $this->assertSame(now()->timestamp, $court['started_at']);
        $this->assertNull($court['w0']);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('setWarmup', 0)->assertDispatched('toast', message: 'No warmup from the next game, courts on now keep their clock')
            ->assertSee('No warmup');
        $this->assertSame(0, $play->fresh()->warmup);

        $play->fresh()->setRotation('rotate');
        $play->fresh()->recordScore(1, 11, 6);
        $this->assertFalse(OpenPlay::isWarming($play->fresh()->boardCourts()[0]));
    }

    public function test_the_create_form_offers_the_warmup_length(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);

        Livewire::actingAs($org)->test(OpenPlays::class)->set('create', true)
            ->assertSee('Warmup')->assertSee('5 min')->assertSee('Each new foursome gets a 3-minute warmup')->assertDontSee('all()', false);
    }
}
