<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_signup_logs_in(): void
    {
        Livewire::test(Signup::class)
            ->set(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com', 'password' => 'secret123'])
            ->call('signup')
            ->assertHasNoErrors()
            ->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticated();
        $this->assertSame('member', User::firstWhere('email', 'juan@example.com')->role);
    }

    public function test_organizer_signup_can_play_while_request_is_pending(): void
    {
        Livewire::withQueryParams(['role' => 'organizer'])->test(Signup::class)
            ->set(['name' => 'Pia Lacson', 'email' => 'pia@example.com', 'password' => 'secret123'])
            ->call('signup')
            ->assertHasErrors(['club', 'city'])
            ->set(['club' => 'Samal Island Dinkers', 'city' => 'Samal', 'courts' => 2])
            ->call('signup')
            ->assertSet('sent', true);

        // One account, two modes: they can play right away.
        $pia = User::firstWhere('email', 'pia@example.com');
        $this->assertAuthenticatedAs($pia);
        $this->assertSame('pending', $pia->organizer_status);
        $this->assertFalse($pia->isOrganizer());
        $this->get(route('organizer.plays'))->assertForbidden();
        $this->get(route('member.dashboard'))->assertOk();

        auth()->logout();
        Livewire::test(Login::class)
            ->set(['email' => 'pia@example.com', 'password' => 'secret123'])
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('member.dashboard'));
        $this->assertStringContainsString('still in review', session('toast'));
    }

    public function test_approved_organizer_switches_between_modes(): void
    {
        $ely = User::factory()->create(['organizer_status' => 'approved']);

        $this->actingAs($ely)->get(route('organizer.plays'))->assertOk();
        $this->assertSame('organizer', $ely->mode());
        $this->get(route('member.dashboard'))->assertOk();
        $this->assertSame('member', $ely->mode());
        $this->assertSame('member.dashboard', $ely->homeRoute());
    }

    public function test_login_rejects_wrong_password_and_accepts_right_one(): void
    {
        User::factory()->create(['email' => 'kai@example.com']);

        Livewire::test(Login::class)
            ->set(['email' => 'kai@example.com', 'password' => 'wrong'])
            ->call('login')
            ->assertHasErrors('email');
        $this->assertGuest();

        Livewire::test(Login::class)
            ->set(['email' => 'kai@example.com', 'password' => 'password'])
            ->call('login')
            ->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_demo_login_is_off_unless_enabled(): void
    {
        $this->seed();

        config(['app.demo' => false]);
        $this->post(route('demo-login'), ['role' => 'admin'])->assertNotFound();
        $this->assertGuest();

        config(['app.demo' => true]);
        $this->post(route('demo-login'), ['role' => 'admin'])->assertRedirect(route('admin.requests'));
        $this->assertAuthenticatedAs(User::firstWhere('email', 'admin@picklex.app'));
        $this->assertSame('Logged in as admin, '.User::where('organizer_status', 'pending')->count().' pending', session('toast'));
    }
}
