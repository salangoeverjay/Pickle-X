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
        $this->get('/no-such-page')->assertNotFound()->assertSee('That one landed out.')->assertSee('Back to home')->assertSee('Create an account');

        $pending = User::factory()->create(['organizer_status' => 'pending']);
        $this->actingAs($pending)->get(route('organizer.plays'))->assertForbidden()
            ->assertSee('This court is reserved')->assertSee('once a PickleX admin approves your club')->assertSee('Go to my PickleX')->assertSee('Switch account');
    }

    public function test_every_error_page_renders(): void
    {
        foreach (['419' => 'This page expired', '429' => 'One ball at a time', '500' => 'We hit the net', '503' => 'Courts are being resurfaced'] as $code => $copy) {
            $html = view("errors.{$code}")->render();
            $this->assertStringContainsString($copy, $html, "errors.{$code}");
            $this->assertStringContainsString('CreativeX Techlabs Inc.', $html);
        }
        $this->assertMatchesRegularExpression('/PX-[0-9A-F]{4}-[0-9A-F]{4}/', view('errors.500')->render());
    }
}
