<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_meet_the_developer_is_public_and_linked(): void
    {
        $this->get(route('about'))->assertOk()
            ->assertSee('Meet the developer · PickleX')->assertSee("Hi, I'm", false)->assertSee('Sole developer of PickleX')
            ->assertSee('What I built for PickleX')->assertSee('Pitched Coders')->assertSee('https://creativexph.com/')
            ->assertSee('/images/jayvee.webp');
        $this->assertFileExists(public_path('images/jayvee.webp'));

        $this->get(route('home'))->assertSee(route('about'))->assertSee('Made with');
        $this->actingAs(User::factory()->create())->get(route('member.dashboard'))->assertSee('Meet the developer');
    }
}
