<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Settings;
use App\Mail\PickleXMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;
use Tests\TestCase;

class PasskeysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function passkey(User $user, string $name): int
    {
        return DB::table('passkeys')->insertGetId(['authenticatable_id' => $user->id, 'name' => $name,
            'credential_id' => 'cred-'.$name, 'data' => '{}', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** The device signed in as $user (the WebAuthn check itself is the package's). */
    private function deviceSignsAs(?User $user): void
    {
        $this->mock(FindPasskeyToAuthenticateAction::class)->shouldReceive('execute')
            ->andReturn($user ? (new Passkey)->setRelation('authenticatable', $user) : null);
    }

    public function test_the_login_page_offers_remember_me_and_passkeys(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Remember me')->assertSee('Log in with a passkey');
    }

    public function test_the_device_gets_real_options_to_sign(): void
    {
        $this->getJson(route('passkeys.options'))->assertOk()->assertJsonStructure(['challenge']);
        $this->assertNotNull(session('passkey-authentication-options'));

        $user = User::factory()->create(['email' => 'kai@picklex.app']);
        $options = json_decode(Livewire::actingAs($user)->test(Settings::class)->call('passkeyOptions')->effects['returns'][0], true);
        $this->assertSame('kai@picklex.app', $options['user']['name']);
        $this->assertArrayHasKey('challenge', $options);
    }

    public function test_a_passkey_logs_you_in(): void
    {
        $user = User::factory()->create();
        $this->deviceSignsAs($user);

        $this->withSession(['passkey-authentication-options' => '{}'])
            ->postJson(route('passkeys.login'), ['response' => '{}'])
            ->assertOk()->assertJson(['redirect' => route($user->homeRoute())]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_bad_passkey_or_a_suspended_account_is_turned_away(): void
    {
        $this->postJson(route('passkeys.login'), ['response' => 'nope'])->assertUnprocessable();
        // No options in the session: nothing was asked for, so nothing can match.
        $this->postJson(route('passkeys.login'), ['response' => '{}'])
            ->assertUnprocessable()->assertJson(['message' => "That passkey didn't work. Try again, or log in with your password."]);

        $this->deviceSignsAs(null);
        $this->withSession(['passkey-authentication-options' => '{}'])
            ->postJson(route('passkeys.login'), ['response' => '{}'])->assertUnprocessable();

        $this->deviceSignsAs(User::factory()->create(['suspended_at' => now()]));
        $this->withSession(['passkey-authentication-options' => '{}'])
            ->postJson(route('passkeys.login'), ['response' => '{}'])
            ->assertUnprocessable()->assertJson(['message' => Login::SUSPENDED]);
        $this->assertGuest();
    }

    public function test_a_new_passkey_sends_a_security_email(): void
    {
        $user = User::factory()->create();
        $this->mock(StorePasskeyAction::class)->shouldReceive('execute')
            ->andReturnUsing(fn () => Passkey::find($this->passkey($user, 'Chrome on macOS')));

        Livewire::actingAs($user)
            ->test(Settings::class)->call('passkeyOptions')->call('storePasskey', '{}')
            ->assertSee('Passkey saved')->assertSee('Next time, tap Log in with a passkey.');
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'passkey-added' && $m->hasTo($user->email) && $m->security
            && str_contains($m->render(), 'A new passkey can log in to your account') && str_contains($m->render(), '1 on your account'));
    }

    public function test_settings_lists_names_and_removes_passkeys(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(Settings::class)
            ->assertSee('No passkeys yet. Add one on this device, then tap Log in with a passkey next time.');

        $id = $this->passkey($user, 'MacBook · Chrome');
        $other = $this->passkey(User::factory()->create(), 'Not mine');

        Livewire::actingAs($user)->test(Settings::class)
            ->assertSee('MacBook · Chrome')->assertSee('Not used yet')->assertDontSee('Not mine')
            ->set('naming', $id)->set('passkeyName', 'Work laptop')->call('namePasskey')
            ->assertDispatched('toast', message: "Passkey saved · we emailed {$user->email} about it")
            ->set('removing', $id)->assertSee('Remove this passkey?')->assertSee('only passkey')
            ->call('removePasskey')->assertDispatched('toast', message: "Removed the passkey for Work laptop · we emailed {$user->email} about it");
        Mail::assertSent(PickleXMail::class, fn ($m) => $m->template === 'passkey-removed' && $m->hasTo($user->email)
            && str_contains($m->render(), 'Work laptop') && str_contains($m->render(), 'Your password still works.'));
        $this->assertDatabaseMissing('passkeys', ['id' => $id]);

        // Someone else's passkey is out of reach.
        Livewire::actingAs($user)->test(Settings::class)->set('removing', $other)->call('removePasskey')->assertNotFound();
        $this->assertDatabaseHas('passkeys', ['id' => $other]);
    }

    public function test_forget_this_device(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(Settings::class)
            ->set('rememberedUntil', 'Nov 1, 2026')->assertSee('Remember me is on. This device keeps you logged in until Nov 1, 2026.')
            ->call('forgetDevice')->assertSet('rememberedUntil', null)
            ->assertDispatched('toast', message: "This device won't keep you logged in anymore")
            ->assertDontSee('Remember me is on');
    }
}
