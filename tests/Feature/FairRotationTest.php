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

    public function test_a_late_walk_in_joins_the_rotation_at_par_instead_of_playing_every_game(): void
    {
        $play = $this->live(8);
        for ($g = 0; $g < 10; $g++) {
            $play->fresh()->recordScore(1, 11, 5); // everyone has played 5
        }
        $late = $play->fresh()->addWalkin('Late Larry')->id;

        $streak = $played = 0;
        for ($g = 0; $g < 9; $g++) {
            $on = in_array($late, [...$play->fresh()->boardCourts()[0]['a']['ids'], ...$play->fresh()->boardCourts()[0]['b']['ids']]);
            $streak = $on ? $streak + 1 : 0;
            $played += $on;
            $this->assertLessThan(2, $streak, "game {$g}: a late arrival still gets a rest between games");
            $play->fresh()->recordScore(1, 11, 5);
        }
        $this->assertGreaterThanOrEqual(3, $played, 'and still gets their fair share');

        // Back from a break: same rule.
        $entry = collect($play->fresh()->queue())->first(fn ($e) => $e['ids'] === [$late]) ?? collect($play->fresh()->queue())->first();
        $play->fresh()->setPaused($entry['id'], true);
        foreach (range(1, 4) as $g) {
            $play->fresh()->recordScore(1, 11, 5);
        }
        $play->fresh()->setPaused($entry['id'], false);
        $book = $play->fresh()->fairBook();
        $others = array_diff(array_keys($book['sg']), $entry['ids']);
        $this->assertGreaterThanOrEqual(min(array_map(fn ($id) => $book['sg'][$id], $others)), $book['sg'][$entry['ids'][0]]);
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

    public function test_courts_can_be_added_and_removed_mid_session(): void
    {
        $play = $this->live(14, 2);
        $c = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourts')->assertSee('Add or remove courts')->assertSet('crt.num', 3)
            ->call('stepCourtNumber', 1)->assertSet('crt.num', 4)
            ->call('addCourt')->assertDispatched('toast');
        $play = $play->fresh();
        $this->assertSame([1, 2, 4], array_column($play->boardCourts(), 'n'));
        $this->assertSame(3, $play->courts);
        $this->assertNotNull($play->boardCourts()[2]['a'], 'auto-fill puts the next group on');

        // A court with a game on: let it finish, then it closes.
        $c->call('askRemoveCourt', 1)->assertSee('Remove Court 1?')->assertSee('Recommended')
            ->call('removeCourt')->assertDispatched('toast', message: 'Court 1 closes after this game · nobody new goes on it')
            ->assertSee('Last game')->assertSee('Keep court');
        $c->call('keepCourt', 1);
        $this->assertArrayNotHasKey('closing', $play->fresh()->boardCourts()[0]);
        $play->fresh()->removeCourt(1, 'finish');
        $play->fresh()->recordScore(1, 11, 4);
        $this->assertSame([2, 4], array_column($play->fresh()->boardCourts(), 'n'));

        // Stop now: not counted, the four go to the front of the line.
        $four = [...$play->fresh()->boardCourts()[0]['a']['ids'], ...$play->fresh()->boardCourts()[0]['b']['ids']];
        $games = $play->games()->count();
        $c->call('askRemoveCourt', 2)->set('crt.how', 'now')->call('removeCourt');
        $play = $play->fresh();
        $this->assertSame($games, $play->games()->count());
        $this->assertEqualsCanonicalizing($four, array_merge(...array_column(array_slice($play->queue(), 0, 4), 'ids')));
        $this->assertSame([4], array_column($play->boardCourts(), 'n'));

        // The last court stays.
        $c->call('askRemoveCourt', 4)->assertDispatched('toast', message: 'At least one court has to stay. To stop for the night, end the session instead.');
        $this->assertCount(1, $play->fresh()->boardCourts());
    }

    public function test_players_subbed_off_court_and_players_added_after_get_their_own_queue_entries(): void
    {
        $play = $this->live(6);
        [$off] = $play->boardCourts()[0]['a']['ids'];
        $play->subOnCourt(1, [$off], [$play->queue()[0]['id']], 'back');
        $late = $play->fresh()->addWalkin('Late Larry');

        $queue = $play->fresh()->queue();
        $ids = array_column($queue, 'id');
        $this->assertSame(count($ids), count(array_unique($ids)), 'every queue entry has its own id');

        // Stepping out the subbed-off player doesn't take the next one with them.
        $offEntry = collect($queue)->first(fn ($e) => $e['ids'] === [$off]);
        $play->fresh()->setPaused($offEntry['id'], true);
        $this->assertEmpty(collect($play->fresh()->queue())->first(fn ($e) => $e['ids'] === [$late->id])['paused'] ?? false);

        // A board that already has a repeated id gets it renumbered when the court board loads.
        $board = $play->fresh()->board;
        $board['queue'][1]['id'] = $board['queue'][0]['id'];
        $play->forceFill(['board' => $board])->save();
        Livewire::actingAs($this->org)->test(CourtBoard::class);
        $ids = array_column($play->fresh()->queue(), 'id');
        $this->assertSame(count($ids), count(array_unique($ids)));
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
