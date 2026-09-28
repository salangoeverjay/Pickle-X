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
        $this->org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 2, 'cap' => 24,
        ]);
        User::factory($players)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
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
        $org = User::factory()->create(['organizer_status' => 'approved']);

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
            ->assertDispatched('toast', fn ($name, $params) => str_starts_with($params['message'], "Court 1: {$names} won 11-5 · ")
                && str_ends_with($params['message'], 'onto the podium'))
            ->assertSet('scoreCourt', null)
            ->assertSee('Latest results')
            ->assertSee("Tonight's standings", false);
        $this->assertSame(1, $play->games()->count());
    }

    public function test_standings_rank_by_wins_then_losses_then_point_difference(): void
    {
        $play = $this->livePlay(8, ['courts' => 2]);
        $court = fn ($n) => collect($play->fresh()->boardCourts())->firstWhere('n', $n);
        $c1 = $court(1);
        $c2 = $court(2);

        $play->recordScore(1, 11, 9); // c1 team a: 1-0, +2
        $play->recordScore(2, 11, 2); // c2 team a: 1-0, +9

        $rows = collect($play->fresh()->standings());
        $this->assertCount(8, $rows);
        $this->assertSame(range(1, 8), $rows->pluck('rank')->all());
        $this->assertEqualsCanonicalizing($c2['a']['ids'], $rows->take(2)->pluck('participant_id')->all());
        $this->assertEqualsCanonicalizing($c1['a']['ids'], $rows->slice(2, 2)->pluck('participant_id')->all());
        $this->assertEqualsCanonicalizing($c2['b']['ids'], $rows->slice(6, 2)->pluck('participant_id')->all());
        $this->assertSame(['w' => 1, 'l' => 0, 'g' => 1, 'pf' => 11, 'pa' => 2, 'diff' => 9], collect($rows->first())->only('w', 'l', 'g', 'pf', 'pa', 'diff')->all());
    }

    public function test_member_dashboard_and_live_view_show_tonights_standing(): void
    {
        $play = $this->livePlay(8, ['courts' => 2]);
        $court = collect($play->boardCourts())->firstWhere('n', 1);
        $play->recordScore(1, 11, 4);

        $winner = $play->participant($court['a']['ids'][0])->user;
        $loser = $play->participant($court['b']['ids'][0])->user;
        $rank = collect($play->fresh()->standings())->firstWhere('participant_id', $court['b']['ids'][0])['rank'];

        $this->actingAs($winner)->get(route('member.dashboard'))->assertSee('Podium · #')->assertSee(' tonight · 1-0');
        $this->actingAs($loser)->get(route('member.dashboard'))->assertSee("#{$rank} tonight · 0-1");
        $this->get(route('live', $play->fresh()->share_code))->assertSee('Every player', false)->assertSee('YOU');
    }

    public function test_winners_stay_on_toast(): void
    {
        $this->livePlay(10, ['courts' => 1, 'rotation' => 'winners']);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openScore', 1)->set('scoreA', 3)->set('scoreB', 11)->call('saveScore')
            ->assertDispatched('toast', fn ($name, $params) => str_contains($params['message'], 'won 11-3 · ') && str_ends_with($params['message'], ' · winners stay on'));
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

        $board->call('fillCourt', 1)->assertDispatched('toast', message: 'Need 4 checked-in players in the queue to fill a court');
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
            // In-app confirm dialog, not the browser's native prompt.
            ->assertSeeHtml('role="alertdialog"')
            ->assertSee('End Monday Night?')
            ->assertSee("This closes the court board and the players' live view. Every logged game and stat stays saved.", false)
            ->assertSee('2 courts are still mid-game.')
            ->assertSeeInOrder(['Games', '1', 'Checked in', '9', 'Running'])
            ->assertDontSeeHtml('wire:confirm')
            ->call('endSession')
            ->assertRedirect(route('organizer.plays'));

        $this->assertSame('done', $play->fresh()->status);
        $this->assertSame('Session ended · 1 game logged', session('toast'));
    }

    public function test_stats_and_check_in_button(): void
    {
        $play = $this->livePlay(11);
        $late = $play->queue()[0];
        $play->participants()->whereIn('id', $late['ids'])->update(['checked_in_at' => null]);

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Ready in queue')->assertSee('2/3')
            ->assertSee('Checked in')->assertSee('10/11')
            ->assertSee('Not checked in yet');

        $board->call('checkInEntry', $late['id'])
            ->assertDispatched('toast', message: $play->names($late['ids']).' checked in')
            ->assertSee('11/11')
            ->assertDontSee('Not checked in yet');
        $this->assertNotNull($play->participant($late['ids'][0])->fresh()->checked_in_at);
    }

    public function test_sub_out_then_sub_in(): void
    {
        $play = $this->livePlay(14); // 6 waiting: 4 up next, 2 free to sub in
        $next = $play->nextUpTeams();
        $out = $next['a']['eids'][0];
        $in = collect($play->queue())->pluck('id')->diff($next['used'])->first();
        $outNames = $play->names(collect($play->queue())->firstWhere('id', $out)['ids']);
        $inNames = $play->names(collect($play->queue())->firstWhere('id', $in)['ids']);
        $outFirst = explode(' & ', $outNames)[0];

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSeeHtml('aria-label="Sub out '.$outNames.'"')
            ->set('subFor', $out)
            ->assertSee("Who subs in for {$outNames}?")
            ->assertSee("{$outFirst} stays in the queue and keeps their spot for when they're ready.", false)
            ->assertSee('Next in line')
            ->call('subIn', $out, $in)
            ->assertSet('subFor', null)
            ->assertDispatched('toast', message: "{$inNames} subs in for {$outNames} · {$outFirst} keeps their spot")
            ->assertSee('Stepped out · keeps spot');

        $queue = collect($play->fresh()->queue());
        $this->assertTrue($queue->firstWhere('id', $out)['paused']);
        $this->assertContains($in, $play->fresh()->nextUpTeams()['used']);
    }

    public function test_sub_panel_without_candidates_and_step_out_back_in(): void
    {
        $play = $this->livePlay(12); // exactly 4 waiting, nobody free to sub in
        $out = $play->nextUpTeams()['a']['eids'][0];
        $names = $play->names(collect($play->queue())->firstWhere('id', $out)['ids']);

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->set('subFor', $out)
            ->assertSee('No checked-in players are free to sub in right now.')
            ->assertSee("Just mark {$names} as stepped out")
            ->call('setPaused', $out, true)
            ->assertSet('subFor', null)
            ->assertDispatched('toast', message: "{$names} stepped out · keeps their spot")
            ->assertSee('Back in');
        $this->assertTrue(collect($play->fresh()->queue())->firstWhere('id', $out)['paused']);

        $board->call('setPaused', $out, false)->assertDispatched('toast', message: "{$names} is back in the rotation");
        $this->assertFalse(collect($play->fresh()->queue())->firstWhere('id', $out)['paused']);
    }

    public function test_share_dialog_renders_qr_and_live_link(): void
    {
        $play = $this->livePlay();
        $url = $play->fresh()->shareUrl();

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Share view')
            ->assertSee('Share view mode')
            ->assertSeeHtml('<svg')
            ->assertSeeHtml('value="'.$url.'"')
            ->assertSeeHtml('href="'.$url.'" target="_blank" rel="noopener"')
            ->assertSee($play->fresh()->share_code)
            ->assertSee('Or share the link')
            ->assertSee('Download QR image')
            ->assertSee('Preview view mode')
            ->assertViewHas('poster', fn ($p) => $p['url'] === $url && count($p['rows']) >= 21 && strlen($p['rows'][0]) === count($p['rows']));
    }

    public function test_organizer_can_add_themselves_to_the_queue(): void
    {
        $play = $this->livePlay();

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Add me to the queue')
            ->call('addMe')
            ->assertDispatched('toast', message: "You're in the queue as a player too")
            ->assertDontSee('Add me to the queue');

        $me = $play->participants()->where('user_id', $this->org->id)->first();
        $this->assertNotNull($me->checked_in_at);
        $this->assertContains($me->id, $play->fresh()->sessionPlayerIds());
    }

    public function test_actions_are_forbidden_without_own_live_session(): void
    {
        $this->livePlay();
        $other = User::factory()->create(['organizer_status' => 'approved']);

        Livewire::actingAs($other)->test(CourtBoard::class)->call('fillCourt', 1)->assertForbidden();
    }
}
