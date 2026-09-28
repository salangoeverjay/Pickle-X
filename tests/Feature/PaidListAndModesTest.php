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

    public function test_paid_list_previews_and_is_added_on_publish(): void
    {
        $org = User::factory()->create(['name' => 'Olga Host', 'organizer_status' => 'approved', 'club' => 'Dink Club']);
        $rico = User::factory()->create(['name' => 'Rico Dizon']);
        User::factory()->create(['name' => 'Mika Reyes', 'email' => 'mika@picklex.app']);
        User::factory()->create(['name' => 'Carla Mendoza']);

        $c = Livewire::actingAs($org)->withQueryParams(['create' => 1])->test(OrganizerPlays::class)
            ->set(['title' => 'Paid Rally', 'venue' => 'Court A', 'cap' => 4])
            ->set('paid', "Rico Dizon\nmika@picklex.app\nCarla\nGrace Ong\nrico dizon")
            ->assertSee('4 paid · 3 linked to accounts · 1 guest')
            ->assertSee('Member · linked')->assertSee('Member · email match')->assertSee('Matched by first name')->assertSee('Guest · no account')
            ->assertSee('from “Carla”', false);

        $c->set('cap', 4)->set('paid', "a\nb\nc\nd\ne")->call('publish')
            ->assertSee('You listed 5 paid players but the cap is 4. Raise the cap or trim the list.');
        $this->assertSame(0, OpenPlay::count());

        $c->set('paid', "Rico Dizon\nmika@picklex.app\nCarla\nGrace Ong")->call('publish')
            ->assertDispatched('toast', message: 'Open play published · 4 paid players added, 3 linked to accounts');

        $play = OpenPlay::sole();
        $this->assertSame(4, $play->participants()->where('paid', true)->count());
        $this->assertTrue($play->participants()->where('user_id', $rico->id)->exists());

        // Upcoming row shows the paid pill.
        $this->actingAs($org)->get(route('organizer.plays'))->assertSee('4 paid · 3 linked · 1 guest');
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
