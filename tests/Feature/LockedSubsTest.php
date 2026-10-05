<?php

namespace Tests\Feature;

use App\Livewire\Organizer\CourtBoard;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Locked partners and subs: on a live court and in the queue. */
class LockedSubsTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    private function livePlay(int $players): OpenPlay
    {
        Mail::fake();
        $this->org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create([
            'organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => 1, 'cap' => 64,
        ]);
        User::factory($players)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();

        return $play->fresh();
    }

    /** A live court whose Team A is a locked pair, with $players in the session. */
    private function lockedOnCourt(int $players = 10): OpenPlay
    {
        $play = $this->livePlay($players);
        $q = $play->queue();
        $play->lockPartners($q[0]['id'], $q[1]['id']);
        $play->fresh()->recordScore(1, 11, 3); // the pair and the next two go on

        $play = $play->fresh();
        $court = $play->boardCourts()[0];
        $side = $court['a']['locked'] ? 'a' : 'b';
        $this->assertTrue($court[$side]['locked'], 'the locked pair is on court');
        if ($side === 'b') {
            [$court['a'], $court['b']] = [$court['b'], $court['a']];
            $board = $play->board;
            $board['courts'][0] = $court;
            $play->forceFill(['board' => $board])->save();
        }

        return $play->fresh();
    }

    private function entryOf(OpenPlay $play, array $ids): ?array
    {
        return collect($play->fresh()->queue())->first(fn ($e) => $e['ids'] == $ids);
    }

    public function test_subbing_one_partner_on_court_unlocks_the_pair(): void
    {
        $play = $this->lockedOnCourt();
        [$off, $stays] = $play->boardCourts()[0]['a']['ids'];
        $in = collect($play->queue())->first(fn ($e) => ! $e['locked'] && count($e['ids']) === 1);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourtSub', 1)->assertSee('Locked pair')
            ->call('pickCourtSub', 'out', [$off])->assertSee('Subbing one partner unlocks')
            ->call('pickCourtSub', 'in', [$in['id']])->call('courtSub');

        $team = $play->fresh()->boardCourts()[0]['a'];
        $this->assertSame([$in['ids'][0], $stays], $team['ids']);
        $this->assertFalse($team['locked'], 'one partner off unlocks the pair');
        $this->assertTrue($this->entryOf($play, [$off])['paused'], 'the one coming off steps out on their own');

        // After the game they go back as singles: unlocked for the rest of the night.
        $game = $play->fresh()->recordScore(1, 11, 4);
        $this->assertFalse((bool) $game->players()->where('participant_id', $stays)->value('locked'));
        $this->assertNotNull($this->entryOf($play, [$stays]));
    }

    public function test_both_partners_off_court_go_back_in_line_still_locked(): void
    {
        $play = $this->lockedOnCourt();
        $pair = $play->boardCourts()[0]['a']['ids'];
        $singles = collect($play->queue())->filter(fn ($e) => ! $e['locked'] && count($e['ids']) === 1)->take(2)->values();

        $c = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourtSub', 1)->call('pickCourtSub', 'out', $pair)->assertSee('Next two')
            ->call('pickCourtSub', 'in', $singles->pluck('id')->all())->call('pickCourtSub', 'after', 'back')->call('courtSub');
        $c->assertDispatched('toast');

        $team = $play->fresh()->boardCourts()[0]['a'];
        $this->assertEqualsCanonicalizing($singles->flatMap(fn ($e) => $e['ids'])->all(), $team['ids']);
        $this->assertFalse($team['locked'], 'two singles going on are not locked');
        $back = $this->entryOf($play, $pair);
        $this->assertTrue($back['locked'], 'the pair goes back in line together');
        $this->assertEmpty($back['paused'] ?? false, 'Back in line now: ready to play');
        $ids = array_column($play->fresh()->queue(), 'id');
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_a_locked_pair_can_go_on_for_a_whole_team(): void
    {
        $play = $this->livePlay(12);
        $q = $play->queue();
        $play->lockPartners($q[2]['id'], $q[3]['id']);
        $play = $play->fresh();
        $court = $play->boardCourts()[0];
        $pairEntry = collect($play->queue())->first(fn ($e) => $e['locked']);

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourtSub', 1)->call('pickCourtSub', 'out', $court['b']['ids'])->assertSee('Locked pair')
            ->call('pickCourtSub', 'in', [$pairEntry['id']])->call('pickCourtSub', 'after', 'out')->call('courtSub');

        $team = $play->fresh()->boardCourts()[0]['b'];
        $this->assertSame($pairEntry['ids'], $team['ids']);
        $this->assertTrue($team['locked'], 'a locked pair stays locked on court');
        foreach ($court['b']['ids'] as $id) {
            $this->assertTrue($this->entryOf($play, [$id])['paused'], 'an unlocked team comes off as two singles');
        }

        $game = $play->fresh()->recordScore(1, 4, 11);
        $this->assertSame(2, $game->players()->where('team', 'b')->where('locked', true)->count(), 'the result records them as partners');
        $this->assertTrue($this->entryOf($play, $pairEntry['ids'])['locked'], 'and they go back in line together');
    }

    public function test_a_one_player_sub_cant_take_a_locked_pair(): void
    {
        $play = $this->livePlay(12);
        $q = $play->queue();
        $play->lockPartners($q[2]['id'], $q[3]['id']);
        $play = $play->fresh();
        $off = $play->boardCourts()[0]['a']['ids'][0];
        $pairEntry = collect($play->queue())->first(fn ($e) => $e['locked']);

        // Not offered for one player, and refused if sent anyway.
        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->call('openCourtSub', 1)->call('pickCourtSub', 'out', [$off])
            ->assertDontSeeHtml('cs-in-'.$pairEntry['id'].'"')
            ->call('pickCourtSub', 'in', [$pairEntry['id']])->call('courtSub')
            ->assertDispatched('toast', message: 'Pick who comes off and a checked-in player to go on.');
        $this->assertContains($off, $play->fresh()->boardCourts()[0]['a']['ids']);
    }

    public function test_a_locked_pair_in_the_queue_can_sub_in_for_another_locked_pair(): void
    {
        $play = $this->livePlay(16);
        $q = $play->queue();
        $play->lockPartners($q[0]['id'], $q[1]['id']);
        $q = $play->fresh()->queue();
        $play->fresh()->lockPartners($q[4]['id'], $q[5]['id']);
        [$out, $in] = collect($play->fresh()->queue())->filter(fn ($e) => $e['locked'])->values()->all();

        Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->set('subFor', $out['id'])->assertSee('Locked pair')
            ->call('subIn', $out['id'], [$in['id']])->assertDispatched('toast');

        $after = collect($play->fresh()->queue());
        $at = $after->search(fn ($e) => $e['id'] === $out['id']);
        $this->assertSame($in['id'], $after[$at - 1]['id'], 'the other pair takes their spot');
        $this->assertTrue($after[$at]['paused'] && $after[$at]['locked'], 'the pair steps out together, still locked');
    }
}
