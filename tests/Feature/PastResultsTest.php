<?php

namespace Tests\Feature;

use App\Livewire\PastResults;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PastResultsTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    /** A concluded one-court session with one game: team a wins 11-6. */
    private function finished(): array
    {
        $this->org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
        $play = OpenPlay::create([
            'organizer_id' => $this->org->id, 'title' => 'Friday Social', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now()->subDay()->setTime(18, 0), 'ends_at' => now()->subDay()->setTime(21, 0), 'courts' => 1, 'cap' => 8,
        ]);
        $players = User::factory(4)->create();
        $players->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();
        $court = $play->fresh()->boardCourts()[0];
        $play->recordScore(1, 11, 6);
        $play->end();

        $winner = $play->participant($court['a']['ids'][0])->user;

        return [$play->fresh(), $players, $winner];
    }

    public function test_member_sees_past_open_plays_and_results(): void
    {
        [$play, , $winner] = $this->finished();

        $this->actingAs($winner)->get(route('member.plays'))->assertOk()
            ->assertSee('Past open plays')->assertSee('Podium finishes')->assertSee('Friday Social')->assertSee('1-0');

        Livewire::actingAs($winner)->test(PastResults::class)
            ->call('show', $play->id)
            ->assertSee('Dink Club · Concluded')->assertSee('Your night')->assertSee('Final standings')
            ->assertSee('Closest game')->assertSee('11-6')->assertSee('Biggest win')
            ->call('close')->assertSet('playId', null);
    }

    public function test_results_are_only_for_people_who_were_there(): void
    {
        [$play] = $this->finished();

        Livewire::actingAs(User::factory()->create())->test(PastResults::class)
            ->call('show', $play->id)->assertSet('playId', null)->assertDontSee('Final standings');

        Livewire::actingAs($this->org)->withQueryParams(['results' => $play->id])->test(PastResults::class)
            ->assertSee('Final standings')->assertDontSee('Your night');
    }

    public function test_organizer_history_and_standings_csv(): void
    {
        [$play] = $this->finished();

        $this->actingAs($this->org)->get(route('organizer.plays'))->assertOk()
            ->assertSee('Concluded open plays')->assertSee('Sessions run')->assertSee('Unique players')->assertSee('won');

        $csv = $this->actingAs($this->org)->get(route('organizer.standings-csv', $play))->assertOk()->streamedContent();
        $this->assertStringStartsWith('Rank,Player,Wins,Losses', $csv);
        $this->assertSame(5, count(array_filter(explode("\n", $csv))));

        $this->actingAs(User::factory()->create(['organizer_status' => 'approved']))
            ->get(route('organizer.standings-csv', $play))->assertNotFound();
    }
}
