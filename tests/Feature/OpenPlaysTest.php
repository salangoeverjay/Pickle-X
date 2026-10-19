<?php

namespace Tests\Feature;

use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpenPlaysTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now()->addDay()->setTime(18, 0), 'ends_at' => now()->addDay()->setTime(21, 0), 'courts' => 1, 'cap' => 8,
        ]);
    }

    public function test_pages_render_for_the_right_role(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->play();

        $this->actingAs($member)->get(route('member.plays'))->assertOk()->assertSee('Find your next game')->assertSee('Friday Rally')
            ->assertSee('1 court, Race to')->assertDontSee('1 courts');
        $this->actingAs($member)->get(route('organizer.plays'))->assertForbidden();
        $this->actingAs($this->org)->get(route('organizer.plays'))->assertOk()->assertSee('Your open plays')->assertSee('Friday Rally');
        $this->actingAs($this->org)->get(route('member.plays'))->assertOk(); // player mode, same account
    }

    public function test_seeded_demo_data_renders(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'kai@picklex.app')->first())->get(route('member.plays'))->assertOk()->assertSee('Check in to get on court')->assertSee('Leave open play')
            ->assertSee('Hosted by')->assertSee('Ely Ramos')->assertSee('Approved organizer, ');
        // An organizer in player mode sees their own sessions as hosted by them.
        $this->actingAs(User::where('email', 'ely@panabodink.ph')->first())->get(route('member.plays'))->assertOk()->assertSee("You're the organizer, ")->assertSee('Hosted by');
        $this->actingAs(User::where('email', 'ely@panabodink.ph')->first())->get(route('organizer.plays'))->assertOk()->assertSee('LIVE NOW')->assertSee('Concluded open plays');
    }

    public function test_member_joins_and_leaves(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $play = $this->play();

        Livewire::actingAs($member)->test(MemberPlays::class)
            ->call('joinPlay', $play->id)
            ->assertDispatched('toast', message: "You're in. See you {$play->whenLabel()}.")
            ->assertSee("You're in, Leave", false)
            ->call('leavePlay', $play->id)
            ->assertDispatched('toast', message: 'You left Friday Rally');

        $this->assertSame(0, $play->playerCount());
    }

    public function test_member_cannot_join_invite_only_or_finished_plays(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($member)->test(MemberPlays::class)->call('joinPlay', $this->play(['visibility' => 'invite'])->id)->assertNotFound();
        Livewire::actingAs($member)->test(MemberPlays::class)->call('joinPlay', $this->play(['status' => 'done'])->id)->assertNotFound();
    }

    public function test_member_sees_their_status_in_a_live_play(): void
    {
        $this->freezeTime();
        $play = $this->play();
        $first = User::factory(4)->create()->map(fn ($u) => $play->join($u));
        $play->start();
        $play->checkIn($first->pluck('id')->all()); // 4 on the only court, queue empty
        $member = User::factory()->create(['role' => 'member']);

        $screen = Livewire::actingAs($member)->test(MemberPlays::class)
            ->call('joinPlay', $play->id)
            ->assertDispatched('toast', message: "You're in the queue. Check in when you reach the courts.")
            ->assertSee('Check in to get on court')
            ->assertSee("You're #1 in line. Tap Check in when you reach the courts.", false)
            ->assertSee('Live board')
            ->assertSee('Leave open play')
            ->call('checkInPlay', $play->id)
            ->assertDispatched('toast', message: 'Checked in at '.now()->format('g:i A').". You're in the rotation.")
            ->assertSee("You're next in line")
            ->assertSee('Waiting for 3 more checked-in players to fill a game. Stay close to the courts.')
            ->assertSee('Checked in '.now()->format('g:i A'));

        // Stepped out (e.g. subbed out by the organizer), then back.
        $play->refresh()->setPaused($play->queue()[0]['id'], true);
        $screen->call('$refresh')
            ->assertSee('You stepped out')
            ->assertSee('You kept your spot at #1', false)
            ->call('stepBackIn', $play->id)
            ->assertDispatched('toast', message: "You're back in the rotation")
            ->assertSee("You're next in line");

        // Three more checked-in players behind make a full next game.
        $more = User::factory(3)->create()->map(fn ($u) => $play->join($u));
        $play->checkIn($more->pluck('id')->all());

        Livewire::actingAs($member)->test(MemberPlays::class)->assertSee("You're up next");
    }

    public function test_member_can_only_check_in_to_plays_they_joined(): void
    {
        $play = $this->play(['status' => 'upcoming']);
        $play->start();

        Livewire::actingAs(User::factory()->create())->test(MemberPlays::class)->call('checkInPlay', $play->id)->assertNotFound();
        Livewire::actingAs(User::factory()->create())->test(MemberPlays::class)->call('stepBackIn', $play->id)->assertNotFound();
    }

    public function test_live_cards_link_to_the_public_view(): void
    {
        $play = $this->play(['status' => 'upcoming']);
        $play->start();

        Livewire::actingAs(User::factory()->create())->test(MemberPlays::class)
            ->assertSee('Watch live')->assertSee(route('live', $play->fresh()->share_code));
    }

    public function test_organizer_publishes_an_open_play(): void
    {
        $this->org->setPref('defaults.courts', 3);
        $this->org->save();

        Livewire::actingAs($this->org)->withQueryParams(['create' => 1])->test(OrganizerPlays::class)
            ->assertSet('create', true)
            ->assertSet('courts', 3)
            ->assertSet('cap', 16)
            ->call('publish')
            ->assertSet('error', 'Add a title and a venue so members know where to go.')
            ->set('title', 'Sunrise Rally')
            ->set('venue', 'Court B')
            ->set('ends', '18:00')
            ->call('publish')
            ->assertSet('error', 'The session has to end after it starts. Pick a later end time.')
            ->set('ends', '17:00') // would be 23 hours overnight
            ->call('publish')
            ->assertSet('error', 'Open plays can run up to 12 hours. Check the end time.')
            ->set('ends', '21:00')
            ->set('date', now()->subDay()->format('Y-m-d'))
            ->call('publish')
            ->assertSet('error', 'Pick a date from today onward.')
            ->set('date', now()->addDays(2)->format('Y-m-d'))
            ->set('raceTo', 15)
            ->call('publish')
            ->assertSet('create', false)
            ->assertDispatched('toast', message: 'Open play published. Members can join now.');

        $play = OpenPlay::where('title', 'Sunrise Rally')->firstOrFail();
        $this->assertSame(['Dink Club', 'upcoming', 3, 16, 15], [$play->club, $play->status, $play->courts, $play->cap, $play->race_to]);
        $this->assertSame('18:00', $play->starts_at->format('H:i'));
    }

    public function test_overnight_sessions_end_the_next_day(): void
    {
        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->set(['title' => 'Late Night Dinks', 'venue' => 'Court B', 'date' => now()->addDay()->format('Y-m-d'), 'starts' => '20:00', 'ends' => '01:00', 'fee' => '150'])
            ->call('publish')->assertSet('error', null);

        $play = OpenPlay::firstWhere('title', 'Late Night Dinks');
        $this->assertSame(300, (int) $play->starts_at->diffInMinutes($play->ends_at));
        $this->assertStringEndsWith('(next day)', $play->timeRange());
        $this->assertSame('₱150', $play->fee, 'a bare number gets the peso sign');
    }

    public function test_organizer_starts_one_session_at_a_time(): void
    {
        $first = $this->play();
        $second = $this->play(['title' => 'Late Rally']);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('startSession', $first->id)
            ->assertRedirect(route('organizer.board'));
        $this->assertSame('live', $first->fresh()->status);
        $this->assertSame('Friday Rally is live. Courts filled from the queue.', session('toast'));

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->assertSee('LIVE NOW')
            ->call('startSession', $second->id)
            ->assertDispatched('toast', message: 'End the current live session first');
        $this->assertSame('upcoming', $second->fresh()->status);
    }

    public function test_organizer_cannot_start_someone_elses_play(): void
    {
        $other = User::factory()->create(['organizer_status' => 'approved']);

        Livewire::actingAs($other)->test(OrganizerPlays::class)->call('startSession', $this->play()->id)->assertForbidden();
    }
}
