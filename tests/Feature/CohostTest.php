<?php

namespace Tests\Feature;

use App\Livewire\JoinLink;
use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\CourtBoard;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CohostTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->withoutDefer();
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Panabo Dink Society']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Saturday Sunrise Rally', 'club' => 'Panabo Dink Society', 'venue' => 'Dinkers Hub',
            'starts_at' => now()->addDays(2)->setTime(6, 0), 'ends_at' => now()->addDays(2)->setTime(9, 0), 'courts' => 1, 'cap' => 18,
        ]);
    }

    public function test_the_host_adds_and_removes_up_to_three_co_hosts(): void
    {
        $play = $this->play();
        $rico = User::factory()->create(['name' => 'Rico Dizon', 'role' => 'member']);
        $play->join($rico);
        $gerald = User::factory()->create(['name' => 'Gerald Uy', 'organizer_status' => 'approved', 'club' => 'Davao Paddle Club']);
        $admin = User::factory()->create(['name' => 'Ada Admin', 'role' => 'admin']);

        $c = Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->assertSee('Add a co-host')
            ->call('openCohosts', $play->id)
            ->assertSee("No co-hosts yet. You're the only one who can start this open play.", false)
            ->assertSeeInOrder(['Rico Dizon', 'Gerald Uy'])->assertSee('Player · joined this')->assertDontSee('Ada Admin')
            ->call('addCohost', $rico->id)->assertDispatched('toast', message: 'Rico Dizon can start and run Saturday Sunrise Rally · we emailed them')
            ->set('cohostQ', 'davao')->assertSee('Gerald Uy')->call('addCohost', $gerald->id)
            ->assertSee('Co-hosts · Rico Dizon, Gerald Uy');
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'cohost' && $m->hasTo($rico->email) && str_contains($m->render(), 'Start the session and fill the courts'));

        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->call('openCohosts', $play->id)->call('addCohost', $admin->id)->assertNotFound();

        $c->call('addCohost', User::factory()->create()->id);
        $c->call('addCohost', User::factory()->create()->id)->assertDispatched('toast', message: 'Up to 3 co-hosts per open play');
        $this->assertSame(3, $play->cohosts()->count());

        $c->call('removeCohost', $rico->id)->assertDispatched('toast', message: 'Rico Dizon is no longer a co-host · we emailed them');
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'cohost' && $m->hasTo($rico->email) && str_contains(html_entity_decode($m->render(), ENT_QUOTES), "You're still on the player list"));
    }

    public function test_an_organizer_co_host_starts_and_runs_it_but_cant_edit_it(): void
    {
        $play = $this->play();
        $gerald = User::factory()->create(['name' => 'Gerald Uy', 'organizer_status' => 'approved', 'club' => 'Davao Paddle Club']);
        $play->cohosts()->attach($gerald);
        User::factory(4)->create()->each(fn ($u) => $play->join($u));

        Livewire::actingAs($gerald)->test(OrganizerPlays::class)
            ->assertSee('Saturday Sunrise Rally')->assertSee('Co-hosting · hosted by Ely Ramos')->assertDontSee('Edit Saturday Sunrise Rally')
            ->call('edit', $play->id)->assertNotFound();
        Livewire::actingAs($gerald)->test(OrganizerPlays::class)->call('openCohosts', $play->id)->assertNotFound();
        Livewire::actingAs($gerald)->test(OrganizerPlays::class)->call('openRoster', $play->id)->assertSee('Upcoming open play · players')->assertDontSee('Edit open play');

        Livewire::actingAs($gerald)->test(OrganizerPlays::class)->call('startSession', $play->id)->assertRedirect(route('organizer.board'));
        $play = $play->fresh();
        $this->assertSame('live', $play->status);
        $this->assertSame($gerald->id, $play->started_by);

        Livewire::actingAs($gerald)->test(CourtBoard::class)->assertSee('co-hosting for Ely Ramos');
        Livewire::actingAs($this->org)->test(CourtBoard::class)->assertSee('by Gerald Uy (co-host)');
        Livewire::actingAs($this->org)->test(OrganizerPlays::class)->assertSee('started by Gerald');
    }

    public function test_a_member_co_host_runs_it_from_player_mode(): void
    {
        $play = $this->play(['visibility' => 'invite']);
        $rico = User::factory()->create(['name' => 'Rico Dizon', 'role' => 'member']);
        $play->cohosts()->attach($rico);
        User::factory(4)->create()->each(fn ($u) => $play->join($u));

        // Invite only, but a co-host sees it, with the tag and Start session.
        Livewire::actingAs($rico)->test(MemberPlays::class)
            ->assertSee("You're a co-host · hosted by Ely Ramos", false)->assertSee('With co-host you')->assertSee('Start session')
            ->call('startSession', $play->id)->assertRedirect(route('organizer.board'));

        $this->actingAs($rico)->get(route('organizer.board'))->assertOk()->assertSee('Court board')->assertSee('co-hosting for Ely Ramos');
        $this->actingAs($rico)->get(route('member.plays'))->assertSee('Open court board');

        // Someone who doesn't run it can't open the court board.
        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('organizer.board'))->assertForbidden();
    }

    public function test_a_co_host_opening_the_share_link_isnt_added_but_can_join_as_a_player(): void
    {
        $play = $this->play();
        $rico = User::factory()->create(['name' => 'Rico Dizon', 'role' => 'member']);
        $play->cohosts()->attach($rico);

        Livewire::actingAs($rico)->test(JoinLink::class, ['code' => $play->linkCode()])
            ->assertSee("You're co-hosting this")->assertSee('You can start it and run the court board with Ely.')
            ->call('join')->assertSee("You're in, Rico.");
        $this->assertTrue($play->activeParticipants()->where('user_id', $rico->id)->exists());
    }
}
