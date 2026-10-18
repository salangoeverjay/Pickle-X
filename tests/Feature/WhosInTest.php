<?php

namespace Tests\Feature;

use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Models\OpenPlay;
use App\Models\User;
use App\Support\PaidList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class WhosInTest extends TestCase
{
    use RefreshDatabase;

    private User $org;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->org = User::factory()->create(['name' => 'Ely Ramos', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
    }

    private function play(array $attrs = []): OpenPlay
    {
        return OpenPlay::create($attrs + [
            'organizer_id' => $this->org->id, 'title' => 'Friday Rally', 'club' => 'Dink Club', 'venue' => 'Court A',
            'starts_at' => now()->addDays(2)->setTime(18, 0), 'ends_at' => now()->addDays(2)->setTime(21, 0), 'courts' => 1, 'cap' => 12, 'fee' => '₱120',
        ]);
    }

    public function test_players_see_who_joined_an_upcoming_play_without_emails_or_payments(): void
    {
        $play = $this->play();
        Livewire::actingAs($kai = User::factory()->create(['role' => 'member']))->test(MemberPlays::class)
            ->assertSee('No one yet')->call('openWho', $play->id)->assertSee('No one has joined yet. Be the first.');

        $mika = User::factory()->create(['name' => 'Mika Lim', 'email' => 'mika@example.com', 'display_name' => 'Mik', 'show_display_name' => true, 'club' => 'Tagum Picklers']);
        $play->join($mika);
        $this->travel(1)->minutes();
        $play->join(User::factory()->create(['name' => 'Ben Tan']));
        $this->travel(1)->minutes();
        $play->addPaidPlayers(PaidList::rows('Grace Ong', [PaidList::key('Grace Ong') => 'guest']));

        $c = Livewire::actingAs($kai)->test(MemberPlays::class)
            ->assertSee("See who's in")
            ->call('openWho', $play->id)
            ->assertSee("Upcoming open play · who's in")
            ->assertSeeInOrder(['Mik', 'Tagum Picklers', 'Ben Tan', 'Grace Ong'])->assertSee('Guest · added by the organizer')
            ->assertDontSee('Mika Lim')->assertDontSee('mika@example.com')->assertDontSee('Unpaid')
            ->assertSee('Only the organizer sees emails and payments.')
            ->assertSee('Guests')->assertSee('Join open play');

        // Names only: a full name someone keeps private doesn't find them.
        $c->set('whoQ', 'mika')->assertSee('No one here matches')
            ->set('whoQ', 'mik')->assertSee('1 of 3 players')->assertDontSee('Ben Tan')
            ->set('whoQ', '')->set('whoF', 'played')->assertSee("You haven't played with anyone here yet.");

        $c->set('whoF', 'all')->call('joinPlay', $play->id)->assertSee('YOU')->assertDontSee('Join open play')->assertSee('see you');

        // Invite-only sessions stay private.
        Livewire::actingAs($kai)->test(MemberPlays::class)->call('openWho', $this->play(['visibility' => 'invite'])->id)->assertNotFound();
    }

    public function test_players_see_where_everyone_is_in_a_live_play(): void
    {
        $play = $this->play();
        $first = User::factory(4)->create()->map(fn ($u) => $play->join($u));
        $play->start();
        $play->checkIn($first->pluck('id')->all());
        $me = User::factory()->create(['role' => 'member']);
        $play->checkIn([$play->join($me)->id]);
        $play->join(User::factory()->create(['name' => 'Late Larry']));
        $play->addWalkin('Walk-in Wes');

        Livewire::actingAs($me)->test(MemberPlays::class)
            ->assertSee("See who's here")
            ->call('openWho', $play->id)
            ->assertSee("Live now · who's here")
            ->assertSee('Court 1')->assertSee('In line · #1')->assertSee('Not checked in')->assertSee('Walk-in guest')
            ->assertSee('On court, then the queue')->assertSee('Watch live')->assertDontSee('Join open play')
            ->set('whoF', 'court')->assertSee('4 of 7 players')
            ->set('whoF', 'away')->assertSee('Late Larry')->assertDontSee('YOU')
            ->call('closeWho')->assertDontSee("Live now · who's here");
    }
}
