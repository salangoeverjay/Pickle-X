<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Livewire\JoinLink;
use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ShareLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->withoutDefer();
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now()->addDays(2)->setTime(18, 0), 'ends_at' => now()->addDays(2)->setTime(21, 0), 'courts' => 2, 'cap' => 12, 'fee' => '₱150',
        ]);
    }

    public function test_organizers_share_set_who_sees_it_and_reset_the_link(): void
    {
        $play = $this->play();
        $c = Livewire::actingAs($this->org)->test(OrganizerPlays::class)
            ->assertSee('Share link')
            ->call('openShare', $play->id)
            ->assertSee('Message to send with it')->assertSee('Join me at Friday Rally')->assertSee('/j/'.$play->fresh()->link_code)
            ->call('setShareVisibility', 'invite')->assertDispatched('toast', message: 'Friday Rally is invite only, only people with the link can find it')
            ->assertSee('Hidden from Open plays.');
        $this->assertSame('invite', $play->fresh()->visibility);

        $old = $play->fresh()->link_code;
        $c->call('resetShareLink')->assertDispatched('toast', message: 'New link ready, the old link stops working')
            ->call('toggleLinkGuests');
        $this->assertNotSame($old, $play->fresh()->link_code);
        $this->assertFalse($play->fresh()->link_guests);
        $this->get(route('join', $old))->assertOk()->assertSee("This link doesn't work anymore", false);

        // Invite only: hidden from Open plays, but the link still works.
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member)->get(route('member.plays'))->assertDontSee('Friday Rally');
    }

    public function test_a_guest_joins_with_just_a_name_and_can_then_make_an_account(): void
    {
        $play = $this->play();
        $code = $play->linkCode();

        Livewire::test(JoinLink::class, ['code' => $code])
            ->assertSee('Join this open play')->assertSee('Join as a guest')
            ->call('pickGuest')->set('name', 'M')->call('joinGuest')->assertSee('Add your name so the organizer can find you at check-in.')
            ->set('name', 'Mika  Lim')->set('email', 'mika@example.com')->call('joinGuest')
            ->assertDispatched('toast')->assertSee("You're in, Mika.")->assertSee('We emailed the details to mika@example.com.')
            ->assertSee('Keep your games')
            ->call('signup')->assertRedirect(route('signup'));

        $guest = $play->activeParticipants()->first();
        $this->assertSame(['Mika Lim', 'mika@example.com', true, null], [$guest->name, $guest->email, $guest->via_link, $guest->user_id]);
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'joined' && $m->hasTo('mika@example.com'));

        // Sign-up carries the name and email, then the link moves the guest spot to the new account.
        Livewire::test(Signup::class)->assertSet('name', 'Mika Lim')->assertSet('email', 'mika@example.com')->assertSee('Create your account to join')
            ->set('password', 'secret-pass-123')->call('signup')->assertRedirect(route('join', $code));
        $mika = User::where('email', 'mika@example.com')->first();
        Livewire::actingAs($mika)->test(JoinLink::class, ['code' => $code])
            ->assertDispatched('toast', message: 'Your guest spot moved to your account')->assertSee("You're in, Mika.");
        $this->assertSame([$mika->id], $play->activeParticipants()->pluck('user_id')->all());
    }

    public function test_logged_in_players_are_added_as_soon_as_the_link_opens(): void
    {
        $play = $this->play(['visibility' => 'invite']);
        $kai = User::factory()->create(['name' => 'Kai Santos', 'role' => 'member']);

        Livewire::actingAs($kai)->test(JoinLink::class, ['code' => $play->linkCode()])
            ->assertSee("Invite only, you're invited")->assertSee("You're in, Kai.")->assertSee('See my open plays')
            ->call('leave')->assertSee('Join as Kai')->call('join')->assertSee("You're in, Kai.");
        $this->assertTrue($play->activeParticipants()->where('user_id', $kai->id)->exists());
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'joined' && $m->hasTo($kai->email));

        // Joined through the link: the invite-only open play now shows in their Open plays.
        Livewire::actingAs($kai)->test(MemberPlays::class)->assertSee('Friday Rally');

        // The organizer sees their own open play; logged out, login comes back here.
        Livewire::actingAs($this->org)->test(JoinLink::class, ['code' => $play->link_code])->assertSee('This is your open play');
        auth()->logout();
        Livewire::test(JoinLink::class, ['code' => $play->link_code])->call('login')->assertRedirect(route('login'));
        $this->assertSame(route('join', $play->link_code), session('url.intended'));
        Livewire::test(Login::class)->assertSee('Log in to join')->assertSee('Friday Rally')->call('cancelJoin');
        $this->assertNull(session('join'));
    }

    public function test_guests_off_full_and_live_open_plays(): void
    {
        $play = $this->play(['cap' => 1, 'link_guests' => false]);
        Livewire::test(JoinLink::class, ['code' => $play->linkCode()])
            ->assertDontSee('Just your name. No account needed.')->assertSee('The organizer asks everyone to join with a PickleX account.');

        $play->update(['link_guests' => true]);
        $play->join(User::factory()->create());
        Livewire::test(JoinLink::class, ['code' => $play->link_code])->assertSee('This open play is full')->assertSee('Ask Ely if one opens up.');

        $play->update(['status' => 'done']);
        Livewire::test(JoinLink::class, ['code' => $play->link_code])->assertSee('This open play has ended');
    }
}
