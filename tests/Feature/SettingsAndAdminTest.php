<?php

namespace Tests\Feature;

use App\Livewire\Admin\Requests;
use App\Livewire\Settings;
use App\Mail\PickleXMail;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        $this->actingAs(User::factory()->create(['organizer_status' => 'approved', 'club' => 'Panabo Dink']))->get(route('settings'))
            ->assertOk()->assertSee('New open plays for Panabo Dink')->assertDontSee('Show me in club rankings');

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('settings'))
            ->assertOk()->assertSee('New organizer request');
    }

    public function test_organizer_in_player_mode_gets_player_settings(): void
    {
        $org = User::factory()->create(['role' => 'member', 'organizer_status' => 'approved', 'club' => 'Panabo Dink']);

        $this->actingAs($org)->withSession(['mode' => 'member'])->get(route('settings'))
            ->assertOk()->assertSee('Member account')->assertSee('Show me in club rankings')->assertSee('Organize open plays')
            ->assertSee('Panabo Dink</strong>.', false)->assertSee('Switch to organizer mode')
            ->assertDontSee('Club and session defaults')->assertDontSee('Someone joins my open play');

        $this->actingAs($org)->withSession(['mode' => 'organizer'])->get(route('settings'))
            ->assertOk()->assertSee('Someone joins my open play')->assertDontSee('Organize open plays');
    }

    public function test_player_requests_organizer_access(): void
    {
        $user = User::factory()->create(['role' => 'member', 'club' => 'Old Club', 'courts' => null]);

        $c = Livewire::actingAs($user)->test(Settings::class)
            ->assertSee('Send organizer request')
            ->set('applyClub', 'Samal Dinkers')->call('requestOrganizer')->assertSee('Add your club name and city.')
            ->set('applyCity', 'Samal')->call('requestOrganizer')
            ->assertDispatched('toast', message: 'Organizer request sent. You keep playing on the same account.')
            ->assertSee('is with a PickleX admin')->assertDontSee('Send organizer request');

        $user->refresh();
        $this->assertSame(['pending', 'Samal Dinkers', 'Samal', 2, 'Applied from an existing player account.'],
            [$user->organizer_status, $user->club, $user->city, $user->courts, $user->organizer_note]);

        $c->call('requestOrganizer')->assertForbidden();

        $user->update(['organizer_status' => 'declined']);
        Livewire::actingAs($user)->test(Settings::class)
            ->assertSee("Your last request for Samal Dinkers wasn't approved.", false)->assertSee('Send organizer request');
    }

    public function test_profile_saves_and_validates(): void
    {
        $taken = User::factory()->create(['role' => 'member']);
        $user = User::factory()->create(['role' => 'member']);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('name', '  ')->call('saveProfile')->assertSee('Your name cannot be empty.')
            ->set(['name' => 'Kai Santos', 'email' => 'nope'])->call('saveProfile')->assertSee('Enter a valid email address.')
            ->set('email', $taken->email)->call('saveProfile')->assertHasErrors(['email' => 'unique'])
            ->set(['email' => 'kai@new.test', 'phone' => '+63 917 000 0000', 'club' => 'Samal Dinkers'])
            ->call('saveProfile')->assertHasNoErrors()->assertDispatched('toast', message: 'Profile saved');

        $user->refresh();
        $this->assertSame(['Kai Santos', 'kai@new.test', 'Samal Dinkers'], [$user->name, $user->email, $user->club]);
    }

    public function test_organizer_cannot_change_club_from_settings(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved', 'club' => 'Panabo Dink']);

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

        $org = User::factory()->create(['organizer_status' => 'approved']);
        Livewire::actingAs($org)->test(Settings::class)
            ->set(['courts' => 6, 'raceTo' => '15', 'rotation' => 'winners'])->call('saveDefaults')
            ->assertHasNoErrors()->assertDispatched('toast');
        $this->assertSame([6, 15, 'winners'], [$org->fresh()->pref('defaults.courts'), $org->fresh()->pref('defaults.race_to'), $org->fresh()->pref('defaults.rotation')]);
    }

    public function test_delete_account(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'member', 'name' => 'Kai Santos']);
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $next = OpenPlay::create(['organizer_id' => $org->id, 'title' => 'Friday', 'club' => 'Club', 'venue' => 'Gym',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'courts' => 1, 'cap' => 8]);
        $next->join($user);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('confirmingDelete', true)->assertSee("Delete {$user->name}")
            ->call('deleteAccount')->assertRedirect(route('home'));

        $this->assertModelMissing($user);
        $this->assertGuest();
        $this->assertSame('Account deleted, we emailed you a confirmation', session('toast'));
        $this->assertSame(0, $next->activeParticipants()->count());
        $this->assertSame('Former player', $next->participants()->first()->name);
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'account-deleted' && $m->hasTo($user->email) && ! $m->data['byAdmin']
            && str_contains($m->render(), 'Your account is deleted, Kai'));
    }

    public function test_admin_requests_page_and_decisions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pia = User::factory()->create(['role' => 'member', 'name' => 'Pia Lacson', 'organizer_status' => 'pending', 'club' => 'Samal Dinkers', 'city' => 'Samal', 'courts' => 1]);
        User::factory()->create(['name' => 'Old Org', 'organizer_status' => 'approved', 'created_at' => now()->subWeek()]);

        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('admin.requests'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.requests'))
            ->assertOk()->assertSee('Pia Lacson')->assertSee('1 court')->assertSee('NEW')->assertSee('No details added.')->assertDontSee('Old Org')
            ->assertDontSee('Already a player, same account');

        $play = OpenPlay::create(['organizer_id' => $admin->id, 'title' => 'Rally', 'club' => 'X', 'venue' => 'Y', 'starts_at' => now(), 'ends_at' => now()->addHours(2), 'courts' => 1, 'cap' => 8]);
        $play->participants()->create(['user_id' => $pia->id, 'name' => $pia->name]);
        $this->actingAs($admin)->get(route('admin.requests'))->assertSee('Already a player, same account');

        Livewire::actingAs($admin)->test(Requests::class)
            ->call('approve', $pia->id)->assertDispatched('toast', message: 'Pia Lacson approved. They can host open plays now.')
            ->assertSee('No pending requests.')
            ->set('tab', 'approved')->assertSee('Old Org')->assertSee('Can host open plays')
            ->call('decline', $pia->id)->assertDispatched('toast', message: "Pia Lacson's request was declined")
            ->call('reopen', $pia->id)->assertDispatched('toast', message: 'Pia Lacson moved back to pending')
            ->call('approve', $admin->id)->assertNotFound();

        $this->assertSame('pending', $pia->fresh()->organizer_status);
    }
}
