<?php

namespace Tests\Feature;

use App\Livewire\Member\PlayedWith;
use App\Livewire\Member\Rankings;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\OpenPlay;
use App\Models\Participant;
use App\Models\User;
use App\Support\MemberStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberStatsTest extends TestCase
{
    use RefreshDatabase;

    private OpenPlay $play;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->parse('2026-09-30 12:00')); // a Wednesday, so "hours ago" stays in this week
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $this->play = OpenPlay::create([
            'organizer_id' => $org->id, 'title' => 'Test', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 2, 'cap' => 24, 'status' => 'done',
        ]);
    }

    /** Log a game; players are Users or walk-in names. */
    private function game(array $a, array $b, bool $aWon, string $ago = '1 hour', ?OpenPlay $play = null): void
    {
        $play ??= $this->play;
        $at = now()->sub($ago);
        $game = Game::create([
            'open_play_id' => $play->id, 'court' => 1, 'score_a' => $aWon ? 11 : 6, 'score_b' => $aWon ? 6 : 11,
            'winner' => $aWon ? 'a' : 'b', 'started_at' => $at, 'played_at' => $at,
        ]);
        foreach (['a' => $a, 'b' => $b] as $team => $players) {
            foreach ($players as $p) {
                $part = $p instanceof User
                    ? Participant::firstOrCreate(['open_play_id' => $play->id, 'user_id' => $p->id], ['name' => $p->name])
                    : Participant::create(['open_play_id' => $play->id, 'name' => $p]);
                GamePlayer::create(['game_id' => $game->id, 'participant_id' => $part->id, 'user_id' => $part->user_id, 'team' => $team, 'won' => ($team === 'a') === $aWon]);
            }
        }
    }

    public function test_pages_render_for_members_only(): void
    {
        $member = User::factory()->create(['name' => 'Zero Games', 'role' => 'member']);
        foreach (['member.dashboard', 'member.played-with', 'member.rankings'] as $route) {
            $this->actingAs($member)->get(route($route))->assertOk();
            // Organizers play on the same account; only admins are kept out.
            $this->actingAs(User::factory()->create(['organizer_status' => 'approved']))->get(route($route))->assertOk();
            $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route($route))->assertForbidden();
        }
        $this->actingAs($member)->get(route('member.dashboard'))->assertSee('Hi, Zero')->assertSee('0%')->assertSee('No games logged yet');
    }

    public function test_stats_points_and_partners(): void
    {
        [$kai, $ben, $mika, $rico] = User::factory(4)->create(['role' => 'member']);
        $this->game([$kai, $ben], [$mika, $rico], true, '3 hours');
        $this->game([$kai, $ben], [$mika, 'Walk In'], true, '2 hours');
        $this->game([$kai, 'Guest Pat'], [$ben, $rico], false, '1 hour');

        $s = new MemberStats($kai);
        $this->assertSame([3, 2, 1, 67], [$s->count(), $s->wins(), $s->losses(), $s->rate()]);
        $this->assertSame(['L', 'W', 'W'], $s->form());
        $this->assertSame('L1', $s->streak());
        $this->assertSame('2-1', $s->lastTen());
        // Walk-ins are skipped.
        $this->assertSame([$ben->id], $s->partners->pluck('user.id')->all());
        $this->assertEqualsCanonicalizing([$mika->id, $rico->id, $ben->id], $s->opponents->pluck('user.id')->all());
        $this->assertSame(2 * 10 + 3 * 3 + 1 * 2, $s->points());
        $this->assertSame('6-11', $s->recent()->first()->score);
        $this->assertSame('Guest Pat', $s->recent()->first()->partner);
        $this->assertSame(3, $s->weeks()[7]->v);
        $this->assertSame('This wk', $s->weeks()[7]->label);
    }

    public function test_rankings_have_a_table_per_club_played_plus_all_clubs(): void
    {
        [$kai, $ben, $mika, $rico] = User::factory(4)->create(['club' => 'Club', 'role' => 'member']);
        $outsider = User::factory()->create(['club' => 'Davao']);
        $rico->setPref('privacy.ranked', false);
        $rico->save();
        $davao = OpenPlay::create(['organizer_id' => $this->play->organizer_id, 'title' => 'Davao night', 'club' => 'Davao', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 1, 'cap' => 8, 'status' => 'done']);
        $this->game([$ben, $mika], [$kai, $rico], true);
        $this->game([$ben, $outsider], [$kai, $mika], true);
        $this->game([$kai, $outsider], ['Walk-in A', 'Walk-in B'], true, '1 hour', $davao);

        // A club table counts everyone who played at that club's open plays, whatever their home club.
        $rows = MemberStats::ranking($kai, null, null, 'Club');
        $this->assertSame([$ben->id, $mika->id, $outsider->id, $kai->id], $rows->pluck('user.id')->all());
        $this->assertSame(2 * 10 + 2 * 3 + 2 * 2, $rows[0]->pts);
        $this->assertSame(4, MemberStats::rankOf($kai, null, 'Club'));
        $this->assertEqualsCanonicalizing([$outsider->id, $kai->id], MemberStats::ranking($kai, null, null, 'Davao')->pluck('user.id')->all());
        $this->assertSame(['Club', 'Davao'], MemberStats::clubsOf($kai)->pluck('name')->all(), 'home club first');
        $this->assertSame('Davao', MemberStats::homeTable($outsider));

        Livewire::actingAs($kai)->test(Rankings::class)
            ->assertSee('Club · This month')->assertSee('Home club')->assertSee('1 open play')->assertSee('All clubs')
            ->assertSee('Players at Club open plays, this month.')->assertSee("That's you", false)->assertSee('pts behind')
            ->assertDontSee($rico->name)
            ->set('club', 'Davao')->assertSee('Davao · This month')->assertSee($outsider->name)
            ->set('club', 'all')->assertSee('All clubs · All time')->assertSee('Every PickleX player, all clubs added up, all time.')
            ->set('club', 'Nowhere')->assertSee('Club · This month');
    }

    public function test_played_with_tabs_search_and_sort(): void
    {
        [$kai, $ben, $mika] = User::factory(3)->sequence(['name' => 'Kai Santos'], ['name' => 'Ben Tan'], ['name' => 'Mika Lim'])->create(['role' => 'member']);
        $this->game([$kai, $ben], [$mika, 'Walk In'], true);

        Livewire::actingAs($kai)->test(PlayedWith::class)
            ->assertSee('Ben Tan')->assertDontSee('Mika Lim')->assertDontSee('Walk In')
            ->set('tab', 'opponents')->assertSee('Mika Lim')
            ->set('search', 'zzz')->assertSee('No one matches that search yet.')
            ->set('search', 'mik')->set('sort', 'rate')->assertSee('Mika Lim');
    }

    public function test_dashboard_shows_live_banner_with_demo_data(): void
    {
        $this->seed();
        $kai = User::firstWhere('email', 'kai@picklex.app');

        $this->actingAs($kai)->get(route('member.dashboard'))
            ->assertOk()->assertSee('Live · Monday Night Open Play')->assertSee('Check in to get on court')->assertSee('Top partners');
        $this->actingAs($kai)->get(route('member.rankings'))->assertOk()->assertSee('Panabo Dink Society');
        $this->actingAs($kai)->get(route('member.played-with'))->assertOk()->assertSee('partners ·', false);
    }

    public function test_members_without_a_club_join_the_club_of_their_first_open_play(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
        $play = OpenPlay::create([
            'organizer_id' => $org->id, 'title' => 'Club night', 'club' => 'Dink Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 1, 'cap' => 8,
        ]);
        $regular = User::factory()->create(['club' => 'Dink Club']);
        $nora = User::factory()->create(['name' => 'Nora Villar']);
        $visitor = User::factory()->create(['club' => 'Samal Dinkers']);
        $others = User::factory(2)->create(['club' => 'Dink Club']);
        foreach ([$regular, $nora, ...$others] as $u) {
            $play->join($u);
        }
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();
        $play->fillCourt(1);
        $play->recordScore(1, 11, 5);
        $play->join($visitor);

        $this->assertSame('Dink Club', $nora->fresh()->club);
        $this->assertSame('Samal Dinkers', $visitor->fresh()->club, 'a home club is never overwritten');
        $this->assertContains('Nora Villar', MemberStats::ranking($regular->fresh())->pluck('user.name'));
        $this->assertContains($nora->id, MemberStats::ranking($nora->fresh())->pluck('user.id'));
    }

    public function test_fresh_accounts_are_not_ranked_until_they_play(): void
    {
        [$kai, $ben, $mika, $rico] = User::factory(4)->create(['club' => 'Panabo']);
        $this->game([$ben, $mika], [$kai, $rico], true);
        $lia = User::factory()->create(['name' => 'Lia Fresh', 'club' => 'Panabo']);

        $this->assertNotContains($lia->id, MemberStats::ranking($lia)->pluck('user.id'));
        $this->assertNull(MemberStats::rankOf($lia));
        $this->assertSame(4, MemberStats::ranking($kai)->count(), 'fresh accounts do not pad the table');
        $this->assertNull(MemberStats::homeTable($lia), 'no clubs until she joins an open play');

        // No clubs yet: only the All clubs table.
        Livewire::actingAs($lia)->test(Rankings::class)
            ->assertSee('All clubs · All time')->assertSee('Join an open play and its club gets its own table here.')
            ->assertSee("You're not ranked yet", false)->assertSee('Not ranked yet · 0 games all time')
            ->assertSee('Play one game at any open play to get on the table.')
            ->assertSee('Up to 15 pts')->assertSee('Top 10 · all clubs')->assertSee('fewer than 10 players are ranked')->assertSee('You, not ranked yet')
            ->assertDontSee("That's you", false);
        // Nothing logged: a guide replaces the empty tiles, tabs and table.
        Livewire::actingAs($lia)->test(PlayedWith::class)->assertSee('No logged games yet')->assertSee('Your people show up here')
            ->assertSee('Find an open play')->assertSee('Play 5 games with the same partner')->assertSee('How this page fills up')
            ->assertSee('Pick one on Open plays and tap Join.')->assertDontSee('Best partner</span><span', false)->assertDontSee('Search players');
        $soon = OpenPlay::create(['organizer_id' => $this->play->organizer_id, 'title' => 'Friday Rally', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(3), 'courts' => 1, 'cap' => 8]);
        $soon->join($lia);
        Livewire::actingAs($lia)->test(PlayedWith::class)->assertSee("You're in · first game coming up")
            ->assertSee('Your first game at Friday Rally adds a partner and two opponents.')->assertSee('See your open play')->assertSee('Done');
        $soon->leave($lia);
        $this->actingAs($lia)->get(route('member.dashboard'))->assertOk()->assertSee('Not ranked yet')->assertDontSee('>0%</span>', false);

        // Joined a club's open play where nobody has played yet: an empty-table note, nobody on the podium.
        $quiet = OpenPlay::create(['organizer_id' => $this->play->organizer_id, 'title' => 'Quiet', 'club' => 'Quiet Club', 'venue' => 'Venue',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'courts' => 1, 'cap' => 8]);
        $quiet->join($solo = User::factory()->create());
        Livewire::actingAs($solo)->test(Rankings::class)->assertSee('Quiet Club · This month')
            ->assertSee('No games logged this month yet at Quiet Club.')->assertDontSee('Top of the table');

        // Played at a club before, just not this month.
        $this->game([$lia, $ben], [$mika, $rico], true, '2 months');
        Livewire::actingAs($lia)->test(Rankings::class)->assertSee('Club · This month')
            ->assertSee('No games at Club open plays this month. Play one to get on the table.')
            ->set('period', 'all')->assertSee("That's you", false);
    }
}
