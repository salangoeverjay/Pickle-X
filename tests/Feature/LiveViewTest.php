<?php

namespace Tests\Feature;

use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveViewTest extends TestCase
{
    use RefreshDatabase;

    /** A live 1-court play: 4 checked-in players on court, $queued waiting (not checked in). */
    private function livePlay(array $queued = []): OpenPlay
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create([
            'organizer_id' => $org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 1, 'cap' => 12,
        ]);
        $players = User::factory(4)->create()->map(fn ($u) => $play->join($u));
        $play->start();
        $play->checkIn($players->pluck('id')->all());
        foreach ($queued as $user) {
            $play->join($user);
        }

        return $play->fresh();
    }

    public function test_guest_can_watch_a_live_play_by_code(): void
    {
        $play = $this->livePlay([User::factory()->create(['name' => 'Rico Dizon', 'email' => 'rico@example.com'])]);

        $this->get(route('live', $play->share_code))
            ->assertOk()
            ->assertSee('Friday Rally · Live · PickleX')
            ->assertSee('LIVE · UPDATES AUTOMATICALLY')
            ->assertSee($play->share_code)
            ->assertSee('1 of 1 courts in play')
            ->assertSee('4 of 5 checked in')
            ->assertSee('Rico Dizon')
            ->assertSee('Not checked in')
            ->assertSee('Log in')
            ->assertDontSee('rico@example.com')
            ->assertDontSee('YOU');
    }

    public function test_member_sees_their_own_row(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos', 'role' => 'member']);
        $play = $this->livePlay([$kai]);

        $this->actingAs($kai)->get(route('live', $play->share_code))
            ->assertOk()->assertSee('Kai Santos')->assertSee('YOU')->assertSee('Exit view');
    }

    public function test_ended_play_shows_the_ended_card(): void
    {
        $play = $this->livePlay();
        $play->end();

        $this->get(route('live', $play->share_code))->assertOk()->assertSee('This open play has ended')->assertDontSee('Queue');
    }

    public function test_unknown_code_is_not_found(): void
    {
        $this->get('/live/NOPE-0000')->assertNotFound();
    }

    public function test_seeded_live_play_renders(): void
    {
        $this->seed();
        $play = OpenPlay::status('live')->firstOrFail();

        $this->get(route('live', $play->share_code))->assertOk()->assertSee($play->title)->assertSee('Latest results');
    }

    public function test_a_session_that_just_started_reads_started_just_now(): void
    {
        $this->seed();
        $play = OpenPlay::status('live')->firstOrFail();
        $play->update(['started_at' => now()]);

        $this->get(route('live', $play->share_code))->assertSee('Started just now')->assertDontSee('just now ago');

        $ids = $play->participants()->limit(3)->pluck('id')->all();
        $first = fn ($i) => $play->participant($ids[$i])->firstName();
        $this->assertSame("{$first(0)}, {$first(1)} & {$first(2)}", $play->names($ids));
        $this->assertSame("{$first(0)} & {$first(1)}", $play->names(array_slice($ids, 0, 2)));
    }
}
