<?php

namespace Tests\Feature;

use App\Livewire\Organizer\OpenPlays as OrganizerPlays;
use App\Livewire\Settings;
use App\Models\OpenPlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_choose_the_name_other_players_see(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);

        Livewire::actingAs($kai)->test(Settings::class)
            ->assertSee('Name other players see')->assertSee('Others see you as')
            ->set('displayName', 'Kaiser the Great Dinker!!')->set('showAs', 'display')->call('saveProfile')
            ->assertSee('Keep your display name to 20 characters or fewer.')
            ->set('displayName', '  ')->call('saveProfile')
            ->assertSee('Add a display name, or choose to show your full name.')
            ->set('displayName', '  Kaiser ')->call('saveProfile')
            ->assertDispatched('toast', message: 'Profile saved · others see you as Kaiser');

        $kai->refresh();
        $this->assertSame(['Kaiser', true, 'Kaiser', 'Kaiser', 'Kai Santos'], [$kai->display_name, $kai->show_display_name, $kai->shownName(), $kai->shownFirst(), $kai->name]);

        Livewire::actingAs($kai)->test(Settings::class)->set('showAs', 'full')->call('saveProfile')->assertDispatched('toast', message: 'Profile saved');
        $this->assertSame('Kai Santos', $kai->fresh()->shownName());
        $this->assertSame('Kaiser', $kai->fresh()->display_name, 'the display name is kept for later');
    }

    public function test_players_see_the_display_name_and_organizers_also_see_the_full_name(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        $kai = User::factory()->create(['name' => 'Kai Santos', 'display_name' => 'Kaiser', 'show_display_name' => true]);
        $play = OpenPlay::create([
            'organizer_id' => $org->id, 'title' => 'Friday Rally', 'club' => 'Club', 'venue' => 'Court A',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'courts' => 1, 'cap' => 12,
        ]);
        $p = $play->join($kai);

        $this->assertSame(['Kaiser', 'Kaiser', 'K', 'Kai Santos'], [$p->fresh()->name, $p->fresh()->firstName(), $p->fresh()->initials(), $p->fresh()->fullName()]);

        Livewire::actingAs($org)->test(OrganizerPlays::class)->call('openRoster', $play->id)
            ->assertSee('Kaiser')->assertSee('Kai Santos · Joined in the app')
            ->set('rosterQ', 'santos')->assertSee('Kai Santos · Joined in the app')
            ->set('rosterQ', 'zzz')->assertSee('No one here matches');
    }
}
