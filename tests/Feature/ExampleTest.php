<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_meta_and_skip_link(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Skip to content')
            ->assertSee('id="main"', false)
            ->assertSee('<meta name="description"', false);
    }

    public function test_error_pages_are_branded(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee("This court doesn't exist")->assertSee('Back to home');

        $pending = User::factory()->create(['organizer_status' => 'pending']);
        $this->actingAs($pending)->get(route('organizer.plays'))->assertForbidden()
            ->assertSee('This court is reserved')->assertSee('once a PickleX admin approves your club')->assertSee('Go to my PickleX');
    }
}
