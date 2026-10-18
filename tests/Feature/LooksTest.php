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

    public function test_collections_have_css_and_are_pickable(): void
    {
        $css = file_get_contents(resource_path('css/looks.css'));
        $ids = collect(Looks::COLLECTIONS)->flatMap(fn ($c) => array_keys($c[4]));
        $this->assertCount(64, $ids);
        foreach ($ids as $id) {
            $this->assertStringContainsString(".px-fr-{$id}{", $css, "border {$id}");
            $this->assertStringContainsString(".px-qb-{$id}{", $css, "queue {$id}");
        }

        $kai = User::factory()->create();
        Livewire::actingAs($kai)->test(Settings::class)
            ->assertDontSee('Shown still here')->assertDontSee('Shinobi flame')
            ->set('lookCat.border', 'col')->assertSee('Shinobi flame')->assertSee('Paused here. Pick one to see it move.')
            ->set('lookCol', 'pr')->assertSee('Pirate · Jolly Rogers')->assertSee('Kraken')
            ->call('setLook', 'frame', 'pr-kraken')->assertDispatched('toast', message: 'Avatar border: Kraken')
            ->set('lookCol', '')->assertSee('Pirate collection, has your pick')->assertSee('Kraken · Pirate')
            ->set('lookCol', 'so')->assertSee('Sorcerer · Azure vortexes')->call('setLook', 'frame', 'so-azure')->assertDispatched('toast', message: 'Avatar border: Azure vortex')
            ->set('lookCol', '')->set('lookCat', [])->assertSee('Collections, you are wearing one of these')->assertSee('Azure vortex')
            ->set('lookTab', 'queue')->call('setLook', 'queue', 'sb-dragon')->assertDispatched('toast', message: 'Queue background: Jade dragon');

        $this->assertSame(['px-fr px-fr-so-azure', 'px-qb px-qb-sb-dragon'], [$kai->fresh()->frameClass(), $kai->fresh()->queueBgClass()]);
    }

    public function test_every_animated_svg_look_has_a_still_version(): void
    {
        // resources/js/looks.js swaps in .px-still off screen, past the animation cap, and for reduced motion.
        // Designs ship these as @media (prefers-reduced-motion) blocks; they must be rewritten as .px-still rules.
        $css = file_get_contents(resource_path('css/looks.css'));
        $ids = collect(Looks::COLLECTIONS)->flatMap(fn ($c) => array_keys($c[4]))->merge(array_keys(Looks::EXCLUSIVE));
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/\.px-fr-'.preg_quote($id).':is\(\.px-still,\.px-still \*\)/', $css, "still border {$id}");
            $this->assertMatchesRegularExpression('/\.px-qb-'.preg_quote($id).':is\(\.px-still,\.px-still \*\)/', $css, "still queue {$id}");
        }
        $this->assertSame(1, substr_count($css, 'prefers-reduced-motion'), 'only the generic CSS-animation stop stays a media query');
    }

    public function test_members_pick_a_border_and_background_that_save_right_away(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);

        Livewire::actingAs($kai)->test(Settings::class)
            ->assertSee('Your look')->assertSee('Changes save right away')->assertSee('Paddle wood')->assertDontSee('Rainbow spin')
            ->set('lookCat.border', 'anim')->assertSee('Rainbow spin')
            ->call('setLook', 'frame', 'spin')->assertDispatched('toast', message: 'Avatar border: Rainbow spin')->assertSee('Saved')
            ->set('lookTab', 'queue')->set('lookCat.queue', 'anim')->assertSee('Bouncing ball')
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

        Livewire::actingAs($kai)->test(Settings::class)->set('lookCat.border', 'ex')
            ->assertSee('For PickleX developers and sponsors. An admin unlocks them.')
            ->call('setLook', 'frame', 'dev')->assertDispatched('toast', message: 'Developer accounts only · an admin unlocks this');
        $this->assertNull($kai->fresh()->frame);
        $this->actingAs($kai)->get(route('admin.developers'))->assertForbidden();

        Livewire::actingAs($admin)->test(Developers::class)
            ->assertSee('No developer accounts yet')
            ->set('q', 'kai s')->assertSee('Make developer')
            ->call('makeDeveloper', $kai->id)->assertDispatched('toast', message: 'Kai Santos is a developer · Developer look unlocked')
            ->assertSee('Unlocked · not worn yet');

        Livewire::actingAs($kai->fresh())->test(Settings::class)->set('lookCat.border', 'ex')
            ->assertSee('Unlocked: developer.')
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

        Livewire::actingAs($kai->fresh())->test(Settings::class)->assertSee('1/4')->set('lookCat.border', 'ex')
            ->assertSee('Thanks for sponsoring. Your Silver look is unlocked.')
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
