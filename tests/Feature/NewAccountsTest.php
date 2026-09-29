<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Livewire\Member\OpenPlays as MemberPlays;
use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_in_signing_up_and_out_show_the_loading_screen(): void
    {
        User::factory()->create(['email' => 'kai@example.com', 'password' => 'password']);
        Livewire::test(Login::class)->set('email', 'kai@example.com')->set('password', 'wrong')->call('login')->assertHasErrors('email');
        $this->assertNull(session('loader'), 'a failed log in never shows it');
        Livewire::test(Login::class)->set('email', 'kai@example.com')->set('password', 'password')->call('login');
        $this->assertSame(['kind' => 'login'], session('loader'));

        $this->post(route('logout'));
        $this->assertSame('logout', session('loader.kind'));

        Livewire::test(Signup::class)->set('role', 'member')->set('name', 'Nora Villar')->set('email', 'nora@example.com')->set('password', 'password123')->call('signup');
        $this->assertSame(['kind' => 'signup', 'first' => 'Nora'], session('loader'));

        auth()->logout();
        Livewire::test(Signup::class)->set('role', 'organizer')->set('name', 'Andrea Ocampo')->set('email', 'andrea@example.com')->set('password', 'password123')
            ->set('club', 'Digos Dink Club')->set('city', 'Digos')->call('signup')
            ->assertDispatched('px-loading', kind: 'signup-org', first: 'Andrea');

        $this->get(route('home'))->assertOk()->assertSee('pxLoader(', false);
    }

    public function test_a_just_approved_organizer_gets_a_welcome_until_their_first_open_play(): void
    {
        $andrea = User::factory()->create(['name' => 'Andrea Ocampo', 'organizer_status' => 'approved', 'club' => 'Digos Dink Club']);

        Livewire::actingAs($andrea)->test(OrganizerPlays::class)
            ->assertSee('Organizer approved')->assertSee("Welcome, Andrea. Let's run your first open play.", false)
            ->assertSee('Digos Dink Club is set up on PickleX.')->assertSee('Create your first open play')->assertSee('Add a payment QR (optional)')
            ->assertDontSee('No upcoming open plays');
        $this->actingAs($andrea)->get(route('organizer.board'))->assertSee('Your first live session opens here.');

        OpenPlay::create(['organizer_id' => $andrea->id, 'title' => 'First', 'club' => 'Digos Dink Club', 'venue' => 'Gym',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'courts' => 1, 'cap' => 8]);
        Livewire::actingAs($andrea)->test(OrganizerPlays::class)->assertDontSee('Organizer approved')->assertSee('Upcoming');
        $this->actingAs($andrea)->get(route('organizer.board'))->assertSee('Start one from your open plays');
    }

    public function test_new_members_see_how_open_plays_work_until_they_join_one(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Dink Club']);
        $play = OpenPlay::create(['organizer_id' => $org->id, 'title' => 'Friday', 'club' => 'Dink Club', 'venue' => 'Gym',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'courts' => 1, 'cap' => 8]);
        $nora = User::factory()->create();

        Livewire::actingAs($nora)->test(MemberPlays::class)->assertSee('New here? Join any open play below.')
            ->call('joinPlay', $play->id)->assertDontSee('New here?');
    }
}
