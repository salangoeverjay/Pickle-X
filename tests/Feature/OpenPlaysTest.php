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
        $this->org = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
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

        $this->actingAs($member)->get(route('member.plays'))->assertOk()->assertSee('Find your next game')->assertSee('Friday Rally');
        $this->actingAs($member)->get(route('organizer.plays'))->assertForbidden();
        $this->actingAs($this->org)->get(route('organizer.plays'))->assertOk()->assertSee('Your open plays')->assertSee('Friday Rally');
        $this->actingAs($this->org)->get(route('member.plays'))->assertForbidden();
    }

    public function test_seeded_demo_data_renders(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'kai@picklex.app')->first())->get(route('member.plays'))->assertOk()->assertSee('Leave open play');
        $this->actingAs(User::where('email', 'ely@panabodink.ph')->first())->get(route('organizer.plays'))->assertOk()->assertSee('LIVE NOW')->assertSee('Past sessions');
    }

    public function test_member_joins_and_leaves(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $play = $this->play();

        Livewire::actingAs($member)->test(MemberPlays::class)
            ->call('join', $play->id)
            ->assertDispatched('toast', message: "You're in! See you {$play->whenLabel()}")
            ->assertSee("You're in · Leave", false)
            ->call('leave', $play->id)
            ->assertDispatched('toast', message: 'You left Friday Rally');

        $this->assertSame(0, $play->playerCount());
    }

    public function test_member_cannot_join_invite_only_or_finished_plays(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($member)->test(MemberPlays::class)->call('join', $this->play(['visibility' => 'invite'])->id)->assertNotFound();
        Livewire::actingAs($member)->test(MemberPlays::class)->call('join', $this->play(['status' => 'done'])->id)->assertNotFound();
    }

    public function test_member_sees_their_status_in_a_live_play(): void
    {
        $play = $this->play();
        User::factory(4)->create()->each(fn ($u) => $play->join($u));
        $play->start(); // 4 on the only court, queue empty
        $member = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($member)->test(MemberPlays::class)
            ->call('join', $play->id)
            ->assertDispatched('toast', message: "You're in — added to the queue")
            ->assertSee('#1 in line')
            ->assertSee('0 players ahead of you · 1 of 1 courts in play')
            ->assertSee('Leave open play');

        // Three more behind make a full next game.
        User::factory(3)->create()->each(fn ($u) => $play->join($u));

        Livewire::actingAs($member)->test(MemberPlays::class)->assertSee("You're up next!");
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
            ->set('ends', '17:00')
            ->call('publish')
            ->assertSet('error', 'The session has to end after it starts.')
            ->set('ends', '21:00')
            ->set('date', now()->subDay()->format('Y-m-d'))
            ->call('publish')
            ->assertSet('error', 'Pick a date from today on.')
            ->set('date', now()->addDays(2)->format('Y-m-d'))
            ->set('raceTo', 15)
            ->call('publish')
            ->assertSet('create', false)
            ->assertDispatched('toast', message: 'Open play published — members can join now');

        $play = OpenPlay::where('title', 'Sunrise Rally')->firstOrFail();
        $this->assertSame(['Dink Club', 'upcoming', 3, 16, 15], [$play->club, $play->status, $play->courts, $play->cap, $play->race_to]);
        $this->assertSame('18:00', $play->starts_at->format('H:i'));
    }

    public function test_organizer_starts_one_session_at_a_time(): void
    {
        $first = $this->play();
        $second = $this->play(['title' => 'Late Rally']);

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->call('startSession', $first->id)
            ->assertRedirect(route('organizer.board'));
        $this->assertSame('live', $first->fresh()->status);
        $this->assertSame('Friday Rally is live — courts filled from the queue', session('toast'));

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->assertSee('LIVE NOW')
            ->call('startSession', $second->id)
            ->assertDispatched('toast', message: 'End the current live session first');
        $this->assertSame('upcoming', $second->fresh()->status);
    }

    public function test_organizer_cannot_start_someone_elses_play(): void
    {
        $other = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved']);

        Livewire::actingAs($other)->test(OrganizerPlays::class)->call('startSession', $this->play()->id)->assertForbidden();
    }
}
