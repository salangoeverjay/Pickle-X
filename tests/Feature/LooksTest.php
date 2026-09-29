<?php

namespace Tests\Feature;

use App\Livewire\Admin\Developers;
use App\Livewire\Admin\Sponsors;
use App\Livewire\Settings;
use App\Models\User;
use App\Support\Looks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_has_ten_animated_and_ten_still_of_each(): void
    {
        foreach (['frame', 'queue'] as $kind) {
            $list = Looks::list($kind);
            $this->assertCount(10, array_filter($list, fn ($l) => $l[1]));
            $this->assertCount(10, array_filter($list, fn ($l) => ! $l[1]));
            $css = file_get_contents(resource_path('css/looks.css'));
            foreach (array_keys($list + Looks::EXCLUSIVE) as $id) {
                $this->assertStringContainsString('.'.($kind === 'frame' ? 'px-fr' : 'px-qb')."-{$id}{", $css, "{$kind} {$id} has CSS");
            }
        }
    }

    public function test_members_pick_a_border_and_background_that_save_right_away(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);

        Livewire::actingAs($kai)->test(Settings::class)
            ->assertSee('Your look')->assertSee('Changes save right away')->assertSee('Rainbow spin')->assertSee('Paddle wood')
            ->call('setLook', 'frame', 'spin')->assertDispatched('toast', message: 'Avatar border: Rainbow spin')->assertSee('Saved')
            ->set('lookTab', 'queue')->assertSee('Bouncing ball')
            ->call('setLook', 'queue', 'confetti')->assertDispatched('toast', message: 'Queue background: Confetti')
            ->call('setLook', 'queue', 'nope');

        $this->assertSame(['spin', 'confetti'], [$kai->fresh()->frame, $kai->fresh()->queue_bg]);
        $this->assertSame('px-fr px-fr-spin', $kai->fresh()->frameClass());
        $this->actingAs($kai->fresh())->get(route('settings'))->assertSee('px-fr px-fr-spin', false)->assertSee('px-qb px-qb-confetti', false);

        Livewire::actingAs($kai)->test(Settings::class)->call('setLook', 'frame', '')->assertDispatched('toast', message: 'Avatar border removed');
        $this->assertNull($kai->fresh()->frame);
    }

    public function test_the_developer_look_is_locked_until_an_admin_unlocks_it(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($kai)->test(Settings::class)
            ->assertSee('For developers and sponsors who help cover PickleX’s costs. An admin unlocks them.')
            ->call('setLook', 'frame', 'dev')->assertDispatched('toast', message: 'Developer accounts only · an admin unlocks this');
        $this->assertNull($kai->fresh()->frame);
        $this->actingAs($kai)->get(route('admin.developers'))->assertForbidden();

        Livewire::actingAs($admin)->test(Developers::class)
            ->assertSee('No developer accounts yet')
            ->set('q', 'kai s')->assertSee('Make developer')
            ->call('makeDeveloper', $kai->id)->assertDispatched('toast', message: 'Kai Santos is a developer · Developer look unlocked')
            ->assertSee('Unlocked · not worn yet');

        Livewire::actingAs($kai->fresh())->test(Settings::class)
            ->assertSee('An admin marked your account as a developer')
            ->call('setLook', 'frame', 'dev')->call('setLook', 'queue', 'dev');
        $this->assertSame('px-fr px-fr-dev', $kai->fresh()->frameClass());

        Livewire::actingAs($admin)->test(Developers::class)
            ->assertSee('Wearing the border and queue background')
            ->call('removeDeveloper', $kai->id)->assertDispatched('toast', message: 'Kai Santos is no longer a developer · look locked');
        $this->assertSame([null, null, false], [$kai->fresh()->frame, $kai->fresh()->queue_bg, $kai->fresh()->is_developer]);
    }

    public function test_sponsor_looks_unlock_per_tier_and_follow_tier_changes(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($kai)->test(Settings::class)
            ->call('setLook', 'frame', 'sp-gold')->assertDispatched('toast', message: 'Gold sponsors only · an admin unlocks this');
        $this->actingAs($kai)->get(route('admin.sponsors'))->assertForbidden();

        Livewire::actingAs($admin)->test(Sponsors::class)
            ->assertSee('Gold sponsor')->assertSee('No sponsors yet')
            ->set('tier', 'silver')->set('q', 'kai')->assertSee('Add as Silver')
            ->call('addSponsor', $kai->id)->assertDispatched('toast', message: 'Kai Santos is a Silver sponsor · Silver look unlocked');

        Livewire::actingAs($kai->fresh())->test(Settings::class)
            ->assertSee('Thanks for sponsoring PickleX. Your Silver sponsor look is unlocked.')->assertSee('1 of 4 unlocked')
            ->call('setLook', 'frame', 'sp-silver')->call('setLook', 'queue', 'sp-silver')
            ->call('setLook', 'frame', 'dev')->assertDispatched('toast', message: 'Developer accounts only · an admin unlocks this');
        $this->assertSame('px-fr px-fr-sp-silver', $kai->fresh()->frameClass());

        Livewire::actingAs($admin)->test(Sponsors::class)
            ->assertSee('Wearing the Silver border and queue background')
            ->call('setTier', $kai->id, 'gold')->assertDispatched('toast', message: 'Kai Santos moved to Gold · Gold sponsor look unlocked');
        $this->assertSame(['sp-gold', 'sp-gold', 'px-qb px-qb-sp-gold'], [$kai->fresh()->frame, $kai->fresh()->queue_bg, $kai->fresh()->queueBgClass()]);

        Livewire::actingAs($admin)->test(Sponsors::class)
            ->call('removeSponsor', $kai->id)->assertDispatched('toast', message: 'Kai Santos is no longer a sponsor · look locked');
        $this->assertSame([null, null, null], [$kai->fresh()->frame, $kai->fresh()->queue_bg, $kai->fresh()->sponsor_tier]);
    }

    public function test_a_sponsor_look_from_another_tier_shows_the_current_tier(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['sponsor_tier' => 'bronze', 'frame' => 'sp-gold', 'queue_bg' => 'dev'])->save();

        $this->assertSame(['px-fr px-fr-sp-bronze', ''], [$u->frameClass(), $u->queueBgClass()]);
    }

    public function test_exclusive_looks_never_render_on_non_developers(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['frame' => 'dev', 'queue_bg' => 'dev'])->save();

        $this->assertSame(['', ''], [$u->frameClass(), $u->queueBgClass()]);
    }
}
