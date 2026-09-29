<?php

namespace Tests\Feature;

use App\Livewire\Organizer\CourtBoard;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BigQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    private function livePlay(int $players, int $courts = 2): OpenPlay
    {
        $this->org = User::factory()->create(['organizer_status' => 'approved']);
        $play = OpenPlay::create([
            'organizer_id' => $this->org->id, 'title' => 'Monday Night', 'club' => 'Club', 'venue' => 'Venue',
            'starts_at' => now(), 'ends_at' => now()->addHours(3), 'courts' => $courts, 'cap' => 64,
        ]);
        User::factory($players)->create()->each(fn ($u) => $play->join($u));
        $play->participants()->update(['checked_in_at' => now()]);
        $play->start();

        return $play;
    }

    public function test_a_locked_pair_steps_out_together_for_two_singles(): void
    {
        $play = $this->livePlay(16); // 8 on court, 8 waiting
        $q = collect($play->queue());
        $play->lockPartners($q[4]['id'], $q[5]['id']); // behind the next four
        $pair = collect($play->queue())->first(fn ($e) => $e['locked']);
        $pairNames = $play->names($pair['ids']);

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->set('subFor', $pair['id'])
            ->assertSee("{$pairNames} are a locked pair, so they step out together and keep their spot in line.")
            ->assertSee('Next two')->assertSee('Only one of them leaving?')
            ->assertSee("Just mark {$pairNames} as stepped out");

        $singles = collect($play->queue())->filter(fn ($e) => ! $e['locked'])->values()->slice(-2)->values();
        $inNames = $play->names($singles->flatMap(fn ($e) => $e['ids'])->all());
        $board->call('subIn', $pair['id'], $singles->pluck('id')->all())
            ->assertDispatched('toast', message: "{$inNames} sub in for {$pairNames} · {$pairNames} keep their spot");

        $after = collect($play->fresh()->queue());
        $at = $after->search(fn ($e) => $e['id'] === $pair['id']);
        $this->assertTrue($after[$at]['paused']);
        $this->assertSame($singles->pluck('id')->all(), [$after[$at - 2]['id'], $after[$at - 1]['id']]);
    }

    public function test_only_one_partner_can_leave(): void
    {
        $play = $this->livePlay(14); // 6 waiting: the pair, two more up next, two free to sub in
        $q = collect($play->queue());
        $play->lockPartners($q[0]['id'], $q[1]['id']);
        $pair = collect($play->queue())->first(fn ($e) => $e['locked']);
        $pid = $pair['ids'][1];
        $first = $play->participant($pid)->firstName();

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->set('subFor', $pair['id'])
            ->call('subOne', $pair['id'], $pid)
            ->assertDispatched('toast', message: "Partners unlocked · who subs in for {$first}?")
            ->assertSee('Next in line');

        $entry = collect($play->fresh()->queue())->first(fn ($e) => $e['ids'] === [$pid]);
        $board->assertSet('subFor', $entry['id']);
        $this->assertFalse(collect($play->fresh()->queue())->contains('locked', true));
    }

    public function test_a_long_queue_can_be_searched_filtered_and_paged(): void
    {
        $play = $this->livePlay(8);
        $late = User::factory(40)->sequence(fn ($seq) => ['name' => $seq->index === 39 ? 'Zed Quimpo' : 'Player '.($seq->index + 1)])->create();
        $late->each(fn ($u) => $play->join($u)); // joined, not checked in

        $board = Livewire::actingAs($this->org)->test(CourtBoard::class)
            ->assertSee('Add players')->assertSee('Find a player in the queue')
            ->assertSee('Showing 30 of 40 spots')->assertSee('Show 10 more')->assertDontSee('Zed Quimpo')
            ->call('showMore')->assertDontSee('Showing 30 of 40')->assertSee('Zed Quimpo')
            ->set('qf', 'here')->assertSee('No one is ready right now.')
            ->set('qf', 'away')->assertSee('Showing 30 of 40 spots')
            ->set('qf', 'all')->set('qq', 'zed quim')->assertSee('Zed Quimpo')->assertDontSee('Showing')
            ->set('qq', 'nobody like this')->assertSee('No one in the queue matches');

        $this->assertSame(30, $board->get('qShow'));
    }

    public function test_the_live_view_lists_twelve_and_counts_the_rest(): void
    {
        $play = $this->livePlay(4, 1); // 4 on court
        $late = User::factory(15)->sequence(fn ($seq) => ['name' => 'Walk In '.($seq->index + 1)])->create();
        $late->each(fn ($u) => $play->join($u));
        $me = $late[14];
        $me->forceFill(['frame' => 'gold', 'queue_bg' => 'dots'])->save();

        $this->get(route('live', $play->share_code))->assertOk()->assertSee('+ 3 more players in line');
        $this->actingAs($me)->get(route('live', $play->share_code))->assertOk()
            ->assertSee('+ 2 more players in line')->assertSee($me->name)
            ->assertSee('px-fr px-fr-gold', false)->assertSee('px-qb px-qb-dots', false);
    }
}
