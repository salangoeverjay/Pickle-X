<?php

namespace Tests\Feature;

use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\CourtBoard;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class FairRotationTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
    }

    private function live(int $players, int $courts = 1, string $rotation = 'rotate'): OpenPlay
    {
        $play = OpenPlay::create([
            'organizer_id' => $this->org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A', 'rotation' => $rotation,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(2), 'courts' => $courts, 'cap' => 30,
        ]);
        $ids = User::factory($players)->create()->map(fn ($u) => $play->join($u)->id)->all();
        $play->start();
        $play->checkIn($ids);

        return $play->fresh();
    }

    public function test_everyone_rotates_plays_fewest_games_first_and_mixes_partners(): void
    {
        $play = $this->live(10);
        $pairs = [];
        $firstRepeat = null;
        for ($g = 1; $g <= 20; $g++) {
            $court = $play->fresh()->boardCourts()[0];
            foreach (['a', 'b'] as $t) {
                $k = OpenPlay::pairKey(...$court[$t]['ids']);
                $firstRepeat ??= isset($pairs[$k]) ? $g : null;
                $pairs[$k] = true;
            }
            $play->fresh()->recordScore(1, 11, 5);
            $games = array_values($play->fresh()->gamesTonight());
            $this->assertLessThanOrEqual(1, max($games) - min(array_pad($games, 10, 0)), "game {$g}: fewest games play first");
        }
        $this->assertGreaterThan(12, $firstRepeat, 'partners mix before anyone repeats');
    }

    public function test_up_next_says_why_and_the_mode_switches_mid_session(): void
    {
        $play = $this->live(12, 2);
        $play->recordScore(1, 11, 5);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Fewest games first')->assertSee('Game mode · changes from the next game')
            ->call('setRotation', 'winners')
            ->assertDispatched('toast', message: 'Winners stay (2 max) from the next game · games on court finish as they are')
            ->assertDontSee('Fewest games first');
        $this->assertSame('winners', $play->fresh()->rotation);
        $this->assertNull($play->fresh()->nextUpTeams()['fair'] ?? null, 'winners stay calls players in line order');
    }

    public function test_sub_on_a_live_court_keeps_the_game_going_and_credits_whoever_finishes(): void
    {
        $play = $this->live(6);
        $court = $play->boardCourts()[0];
        [$off, $stays] = $court['a']['ids'];
        $sub = $play->queue()[0];

        $c = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourtSub', 1)->assertSee('Sub on Court 1')->assertSee('Pick who comes off')
            ->call('pickCourtSub', 'out', [$off])->assertSee('Next in line')
            ->call('pickCourtSub', 'in', [$sub['id']])->call('pickCourtSub', 'after', 'out')
            ->call('courtSub')->assertSet('csub', null);
        $c->assertDispatched('toast');

        $play = $play->fresh();
        $this->assertSame([$sub['ids'][0], $stays], $play->boardCourts()[0]['a']['ids']);
        $this->assertSame($court['started_at'], $play->boardCourts()[0]['started_at'], 'the timer keeps going');
        $back = collect($play->queue())->last();
        $this->assertSame([[$off], true], [$back['ids'], $back['paused']]);

        $game = $play->recordScore(1, 11, 7);
        $this->assertEqualsCanonicalizing([$sub['ids'][0], $stays], $game->players()->where('team', 'a')->pluck('participant_id')->all());
        $this->assertSame($off, $game->subs[0]['out']);
        Livewire::actingAs($this->org)->test(CourtBoard::class)->assertSee('Sub: ');

        // Done for the night: they leave the session.
        $court = $play->fresh()->boardCourts()[0];
        $out = $court['b']['ids'][0];
        $in = collect($play->fresh()->queue())->first(fn ($e) => empty($e['paused']));
        $play->fresh()->subOnCourt(1, [$out], [$in['id']], 'leave');
        $this->assertNotNull($play->participants()->find($out)->left_at);
    }

    public function test_members_see_their_partners_tonight(): void
    {
        $play = $this->live(4);
        $me = $play->participantMap()->first()->user;
        $play->recordScore(1, 11, 5);

        Livewire::actingAs($me)->test(MemberPlays::class)
            ->assertSee('Partners tonight')->assertSee('different partner');
    }
}
