<?php

namespace Tests\Feature;

use App\Livewire\Admin\OpenPlays;
use App\Mail\PickleXMail;
use App\Models\GamePlayer;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOpenPlaysTest extends TestCase
{
    use RefreshDatabase;

    private function concluded(User $org, string $title, $players, int $games): OpenPlay
    {
        $play = OpenPlay::create([
            'organizer_id' => $org->id, 'title' => $title, 'club' => 'Davao Paddle Club', 'venue' => 'Davao City',
            'starts_at' => now()->subDays(2)->setTime(19, 0), 'ends_at' => now()->subDays(2)->setTime(20, 0), 'courts' => 1, 'cap' => 8,
        ]);
        foreach ($players as $p) {
            $play->join($p);
        }
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();
        foreach (range(1, $games) as $g) {
            $court = collect($play->fresh()->boardCourts())->first(fn ($c) => $c['a']);
            $play->recordScore($court['n'], 11, 4);
        }
        $play->end();

        return $play;
    }

    public function test_an_admin_deletes_a_test_open_play_and_its_games_come_off_everyones_stats(): void
    {
        Mail::fake();
        $this->withoutDefer();
        $admin = User::factory()->create(['role' => 'admin']);
        $gerald = User::factory()->create(['name' => 'Gerald Uy', 'email' => 'gerald@davaopaddle.ph', 'organizer_status' => 'approved', 'club' => 'Davao Paddle Club']);
        $players = User::factory(8)->create();
        $kai = $players[0];
        $test = $this->concluded($gerald, 'TEST - please ignore', $players, 6);
        $real = $this->concluded($gerald, 'Sunday Social', $players, 2);
        $kaiGames = fn () => GamePlayer::where('user_id', $kai->id)->count();
        $before = $kaiGames();

        $c = Livewire::actingAs($admin)->test(OpenPlays::class)
            ->assertSee('Concluded open plays')->assertSeeInOrder(['TEST - please ignore', 'Looks like a test'])
            ->set('tab', 'test')->assertSee('TEST - please ignore')->assertDontSee('Sunday Social')
            ->call('askDelete', $test->id)->assertSet('why', 'test')
            ->assertSee('Delete TEST - please ignore?')->assertSee('Hosted by Gerald Uy, gerald@davaopaddle.ph')
            ->assertSee("Comes off these 8 players' stats")->assertSee('We email Gerald Uy that it was deleted, with this reason.')
            ->call('delete')
            ->assertDispatched('toast', message: "TEST - please ignore deleted, 6 games came off 8 players' stats, we emailed Gerald");

        $this->assertModelMissing($test);
        $this->assertModelExists($real);
        $this->assertSame(0, $test->games()->count());
        $this->assertLessThan($before, $kaiGames());
        $c->assertSee('Deleted')->assertSee('hosted by Gerald Uy, reason: a test')->assertSee('Gerald was emailed');

        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'play-deleted' && $m->hasTo($gerald->email)
            && str_contains(html_entity_decode($m->render(), ENT_QUOTES), 'because it was a test') && str_contains($m->render(), 'Trying PickleX out again?'));
        Mail::assertSent(PickleXMail::class, 1);
    }

    public function test_only_admins_reach_it_and_only_concluded_open_plays_can_be_deleted(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $this->actingAs($org)->get(route('admin.plays'))->assertForbidden();

        $upcoming = OpenPlay::create(['organizer_id' => $org->id, 'title' => 'Test night', 'club' => 'Club', 'venue' => 'Court',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 1, 'cap' => 8]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.plays'))->assertOk()->assertSee('No concluded open plays yet.');
        Livewire::actingAs($admin)->test(OpenPlays::class)->call('askDelete', $upcoming->id)->assertNotFound();
        $this->assertModelExists($upcoming);
    }
}
