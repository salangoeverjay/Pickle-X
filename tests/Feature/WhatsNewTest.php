<?php

namespace Tests\Feature;

use App\Livewire\Admin\WhatsNew as AdminWhatsNew;
use App\Livewire\WhatsNew;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsNewTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_writes_a_draft_then_publishes_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'PickleX Admin']);
        User::factory(3)->create();
        User::factory()->create(['organizer_status' => 'approved']);

        Livewire::actingAs($admin)->test(AdminWhatsNew::class)
            ->assertSee('Nothing published yet. Write your first update.')
            ->call('edit')->assertSee('New update')->call('save')->assertSee('Give the update a title.')
            ->set('title', 'Warmup countdown on every court')->call('save')->assertSee('Add a short description of what changed.')
            ->set('body', 'Each new foursome gets a short warmup first.')->call('pick', 'audience', 'member')
            ->assertSee('Shows once to 3 people who only play')
            ->call('save')->assertDispatched('toast', message: 'Draft saved, only admins can see it')->assertSet('tab', 'draft')
            ->assertSee('Players only, Draft, saved Today');

        $n = News::sole();
        $this->assertFalse($n->isLive());
        Livewire::actingAs($admin)->test(AdminWhatsNew::class)
            ->call('publish', $n->id)->assertDispatched('toast', message: 'Published, shows once to 3 people next time they open PickleX')
            ->assertSee('0 of 3 read')->assertSee('Published Today by PickleX Admin');
        $this->assertTrue($n->fresh()->isLive());

        $this->actingAs(User::factory()->create())->get(route('admin.whats-new'))->assertForbidden();
    }

    public function test_players_see_it_once_then_keep_it_in_whats_new(): void
    {
        $kai = User::factory()->create(['name' => 'Kai Santos']);
        $all = News::create(['title' => 'Warmup countdown on every court', 'body' => 'A short warmup first.', 'kind' => 'feature', 'audience' => 'all', 'published_at' => now()]);
        News::create(['title' => 'Organizer fix', 'body' => 'Only for organizers.', 'kind' => 'fix', 'audience' => 'organizer', 'published_at' => now()]);
        News::create(['title' => 'Jerseys draft', 'body' => 'Not published.', 'kind' => 'feature', 'audience' => 'all']);

        $this->actingAs($kai)->get(route('member.dashboard'))
            ->assertSee('One update since you were last here')->assertSee('Warmup countdown on every court')
            ->assertDontSee('Organizer fix')->assertDontSee('Jerseys draft')->assertSee('1 new');

        // Closing the popup marks what it showed as read; it doesn't come back.
        $this->actingAs($kai)->postJson(route('whats-new.read'), ['ids' => [$all->id]])->assertNoContent();
        $this->actingAs($kai)->get(route('member.dashboard'))->assertDontSee('since you were last here');

        Livewire::actingAs($kai)->test(WhatsNew::class)
            ->assertSee("You're all caught up")->assertSee('Warmup countdown on every court')->assertDontSee('Organizer fix');
    }

    public function test_opening_whats_new_marks_unread_as_read_and_admins_dont_get_it(): void
    {
        $org = User::factory()->create(['organizer_status' => 'approved']);
        News::create(['title' => 'Organizer fix', 'body' => 'Only for organizers.', 'kind' => 'fix', 'audience' => 'organizer', 'published_at' => now()]);

        Livewire::actingAs($org)->test(WhatsNew::class)
            ->assertSee('1 new update, marked as read')->assertSee('Organizers only')
            ->set('tab', 'enhancement')->assertSee('Nothing here yet.');
        $this->assertSame(0, News::unreadBy($org)->count());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('whats-new'))->assertNotFound();
        $this->assertSame(0, News::unreadBy($admin)->count());
    }

    public function test_deleting_an_update_takes_it_off_everyones_whats_new(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kai = User::factory()->create();
        $n = News::create(['title' => 'Old news', 'body' => 'Gone soon.', 'kind' => 'enhancement', 'audience' => 'all', 'published_at' => now()]);

        Livewire::actingAs($admin)->test(AdminWhatsNew::class)
            ->set('deleting', $n->id)->assertSee("1 person who hasn't read it yet won't see it.")
            ->call('delete')->assertDispatched('toast', message: "Update deleted, it's gone from everyone's What's new, Old news");
        $this->assertSame(0, News::unreadBy($kai)->count());
    }
}
