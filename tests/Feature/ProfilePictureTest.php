<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    private function jpeg(int $size = 256): string
    {
        $img = imagecreatetruecolor($size, $size);
        imagefill($img, 0, 0, imagecolorallocate($img, 211, 243, 71));
        ob_start();
        imagejpeg($img, null, 88);

        return 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
    }

    public function test_presets_initials_and_remove(): void
    {
        $user = User::factory()->create();
        $settings = Livewire::actingAs($user)->test(Settings::class)->assertSee('Choose an avatar');

        $settings->call('usePreset', 'dink')->assertReturned(fn ($r) => $r['ok'] && str_ends_with($r['url'], '/avatars/presets/dink.svg'))
            ->assertDispatched('toast', message: 'Avatar updated: Dink shot');
        $this->assertSame('preset:dink', $user->fresh()->avatar);
        $this->assertFileExists(public_path('avatars/presets/dink.svg'));

        $settings->call('useInitials', '#BDEBE3')->assertDispatched('toast', message: 'Showing your initials');
        $this->assertNull($user->fresh()->avatar);
        $this->assertSame('#BDEBE3', $user->fresh()->avatar_color);

        $settings->call('usePreset', 'not-a-preset')->assertStatus(422);
    }

    public function test_photo_upload_is_validated_stored_and_served(): void
    {
        $user = User::factory()->create();
        $settings = Livewire::actingAs($user)->test(Settings::class);

        // Wrong size, not a JPEG, junk: all rejected, nothing stored.
        foreach ([$this->jpeg(300), 'data:image/png;base64,iVBORw0KGgo=', 'data:image/jpeg;base64,bm90IGFuIGltYWdl'] as $bad) {
            $settings->call('usePhoto', $bad)->assertReturned(fn ($r) => ! $r['ok']);
        }
        $this->assertNull($user->fresh()->avatar);

        $settings->call('usePhoto', $this->jpeg())->assertReturned(fn ($r) => $r['ok'])->assertDispatched('toast', message: 'Profile photo updated');
        $user->refresh();
        $this->assertStringStartsWith('photo:', $user->avatar);

        $this->get($user->avatarUrl())->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('avatar.photo', ['user' => $user->id, 'version' => 'aaaaaaaaaaaa']))->assertNotFound();

        // Shows up across the app.
        $this->actingAs($user)->get(route('member.dashboard'))->assertSee($user->avatarUrl(), false);

        $settings->call('removePicture')->assertDispatched('toast', message: 'Picture removed. Your initials are showing again.');
        $this->assertNull($user->fresh()->avatar);
        $this->assertDatabaseMissing('avatar_photos', ['user_id' => $user->id]);
    }

    public function test_all_42_avatars_are_grouped_and_usable(): void
    {
        $groups = collect(User::AVATAR_GROUPS);
        $this->assertSame(array_keys(User::AVATAR_PRESETS), $groups->flatMap(fn ($g) => $g[2])->all(), 'every preset is in exactly one group, in order');
        $this->assertCount(42, User::AVATAR_PRESETS);
        foreach (array_keys(User::AVATAR_PRESETS) as $id) {
            $this->assertFileExists(public_path("avatars/presets/{$id}.svg"));
        }

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('settings'))->assertOk()
            ->assertSee('Avatar categories')->assertSee('Smileys')->assertSee('Headphones')->assertSee('42 avatars made for PickleX');

        Livewire::actingAs($user)->test(Settings::class)->call('usePreset', 'fox');
        $this->assertSame('preset:fox', $user->fresh()->avatar);
    }
}
