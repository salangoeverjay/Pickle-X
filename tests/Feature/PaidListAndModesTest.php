<?php

namespace Tests\Feature;

use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaidListAndModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_list_suggests_accounts_and_only_links_what_you_pick(): void
    {
        $org = User::factory()->create(['name' => 'Olga Host', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
        $rico = User::factory()->create(['name' => 'Rico Dizon']);
        $mika = User::factory()->create(['name' => 'Mika Reyes', 'email' => 'mika@picklex.app']);
        User::factory()->create(['name' => 'Carla Mendoza']);

        $c = Livewire::actingAs($org)->withQueryParams(['create' => 1])->test(OrganizerPlays::class)
            ->set(['title' => 'Paid Rally', 'venue' => 'Court A', 'cap' => 4])
            ->set('paid', "Rico Dizon\nmika@picklex.app\nCarla\nGrace Ong\nrico dizon")
            ->assertSee('4 names · 0 linked · 3 to review · 1 guest')
            ->assertSee('Needs review')->assertSee('Same name')->assertSee('Email matches')->assertSee('Same first name')
            ->assertSee('Guest · no matching account')
            ->assertSee('None of these · keep as guest');

        // Nothing links on its own: pick Rico and Mika, keep Carla as a guest.
        $c->call('linkPaid', 'rico dizon', $rico->id)->call('linkPaid', 'mika@picklex.app', $mika->id)->call('keepGuest', 'carla')
            ->assertSee('4 names · 2 linked · 2 guests')
            ->assertSee('Linked to Rico Dizon')->assertSee('Guest · you chose not to link an account')
            ->call('unlinkPaid', 'carla')->assertSee('1 to review')->call('keepGuest', 'carla');

        $c->set('cap', 4)->set('paid', "a\nb\nc\nd\ne")->call('publish')
            ->assertSee('You listed 5 paid players but the cap is 4. Raise the cap or trim the list.');
        $this->assertSame(0, OpenPlay::count());

        $c->set('paid', "Rico Dizon\nmika@picklex.app\nCarla\nGrace Ong")->call('publish')
            ->assertDispatched('toast', message: 'Open play published · 4 paid players added, 2 linked to accounts');

        $play = OpenPlay::sole();
        $this->assertSame(4, $play->participants()->where('paid', true)->count());
        $this->assertTrue($play->participants()->where('user_id', $rico->id)->exists());
        $this->assertTrue($play->participants()->whereNull('user_id')->where('name', 'Carla')->exists(), 'Carla stays a guest');

        $this->actingAs($org)->get(route('organizer.plays'))->assertSee('4 paid · 2 linked · 2 guests');
    }

    public function test_unlinked_suggestions_show_on_the_review(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        User::factory()->create(['name' => 'Rico Dizon']);

        Livewire::actingAs($org)->test(OrganizerPlays::class)
            ->call('openCreate')->set(['title' => 'Rally', 'venue' => 'Court A', 'paid' => 'Rico Dizon'])->call('showReview')
            ->assertSee("1 name has a possible account you haven't linked. It joins as a guest unless you go back and link it.");
    }

    public function test_publish_without_paid_list_keeps_old_toast(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);

        Livewire::actingAs($org)->test(OrganizerPlays::class)
            ->set(['title' => 'Free Rally', 'venue' => 'Court A'])->call('publish')
            ->assertDispatched('toast', message: 'Open play published. Members can join now.');
    }

    public function test_live_card_shows_checked_in_chip(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'ely@panabodink.ph')->first())->get(route('organizer.plays'))
            ->assertOk()->assertSeeInOrder(['17/20', 'checked in']);
    }
}
