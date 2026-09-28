<?php

namespace Tests\Feature;

use App\Livewire\Admin\Requests;
use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_renders_for_every_role(): void
    {
        $this->get(route('settings'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('settings'))
            ->assertOk()->assertSee('Member account')->assertSee('Show me in club rankings')->assertDontSee('Club and session defaults');

        $this->actingAs(User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved', 'club' => 'Panabo Dink']))->get(route('settings'))
            ->assertOk()->assertSee('New open plays for Panabo Dink')->assertDontSee('Show me in club rankings');

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('settings'))
            ->assertOk()->assertSee('New organizer request');
    }

    public function test_profile_saves_and_validates(): void
    {
        $taken = User::factory()->create(['role' => 'member']);
        $user = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('name', '  ')->call('saveProfile')->assertSee('Your name cannot be empty.')
            ->set(['name' => 'Kai Santos', 'email' => 'nope'])->call('saveProfile')->assertSee('Enter a valid email address.')
            ->set('email', $taken->email)->call('saveProfile')->assertHasErrors(['email' => 'unique'])
            ->set(['email' => 'kai@new.test', 'phone' => '+63 917 000 0000', 'club' => 'Samal Dinkers', 'avatar' => '#BDEBE3'])
            ->call('saveProfile')->assertHasNoErrors()->assertDispatched('toast', message: 'Profile saved');

        $user->refresh();
        $this->assertSame(['Kai Santos', 'kai@new.test', 'Samal Dinkers', '#BDEBE3'], [$user->name, $user->email, $user->club, $user->avatar_color]);
    }

    public function test_organizer_cannot_change_club_from_settings(): void
    {
        $org = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved', 'club' => 'Panabo Dink']);

        Livewire::actingAs($org)->test(Settings::class)->set('club', 'Other')->call('saveProfile')->assertHasNoErrors();

        $this->assertSame('Panabo Dink', $org->fresh()->club);
    }

    public function test_password_update(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($user)->test(Settings::class)
            ->call('savePassword')->assertSee('Enter your current password.')
            ->set(['currentPassword' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->call('savePassword')->assertHasErrors(['currentPassword' => 'current_password'])
            ->set(['currentPassword' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])
            ->call('savePassword')->assertSee('Your new password needs at least 8 characters.')
            ->set(['password' => 'newpass123', 'password_confirmation' => 'other123'])
            ->call('savePassword')->assertSee('The new passwords don&#039;t match.', false)
            ->set(['password' => 'password', 'password_confirmation' => 'password'])
            ->call('savePassword')->assertSee('Pick a new password that is different from the current one.')
            ->set(['password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->call('savePassword')->assertHasNoErrors()->assertSet('currentPassword', '')->assertDispatched('toast', message: 'Password updated');

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_switches_and_defaults_save(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $component = Livewire::actingAs($member)->test(Settings::class)->call('toggle', 'privacy.ranked')->call('toggle', 'notify.recap');
        $this->assertFalse($member->fresh()->pref('privacy.ranked'));
        $this->assertTrue($member->fresh()->pref('notify.recap'));
        $component->call('toggle', 'notify.joins')->assertForbidden();
        Livewire::actingAs($member)->test(Settings::class)->call('saveDefaults')->assertForbidden();

        $org = User::factory()->create(['role' => 'organizer', 'organizer_status' => 'approved']);
        Livewire::actingAs($org)->test(Settings::class)
            ->set(['courts' => 6, 'raceTo' => '15', 'rotation' => 'winners'])->call('saveDefaults')
            ->assertHasNoErrors()->assertDispatched('toast');
        $this->assertSame([6, 15, 'winners'], [$org->fresh()->pref('defaults.courts'), $org->fresh()->pref('defaults.race_to'), $org->fresh()->pref('defaults.rotation')]);
    }

    public function test_delete_account(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('confirmingDelete', true)->assertSee("Delete {$user->name}")
            ->call('deleteAccount')->assertRedirect(route('home'));

        $this->assertModelMissing($user);
        $this->assertGuest();
    }

    public function test_admin_requests_page_and_decisions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pia = User::factory()->create(['name' => 'Pia Lacson', 'role' => 'organizer', 'organizer_status' => 'pending', 'club' => 'Samal Dinkers', 'city' => 'Samal', 'courts' => 1]);
        User::factory()->create(['name' => 'Old Org', 'role' => 'organizer', 'organizer_status' => 'approved', 'created_at' => now()->subWeek()]);

        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('admin.requests'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.requests'))
            ->assertOk()->assertSee('Pia Lacson')->assertSee('1 court')->assertSee('NEW')->assertSee('No details added.')->assertDontSee('Old Org');

        Livewire::actingAs($admin)->test(Requests::class)
            ->call('approve', $pia->id)->assertDispatched('toast', message: 'Pia Lacson approved — they can host open plays now')
            ->assertSee('No pending requests.')
            ->set('tab', 'approved')->assertSee('Old Org')->assertSee('Can host open plays')
            ->call('decline', $pia->id)->assertDispatched('toast', message: "Pia Lacson's request was declined")
            ->call('reopen', $pia->id)->assertDispatched('toast', message: 'Pia Lacson moved back to pending')
            ->call('approve', $admin->id)->assertNotFound();

        $this->assertSame('pending', $pia->fresh()->organizer_status);
    }
}
