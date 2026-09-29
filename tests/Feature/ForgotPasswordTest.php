<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Mail\PickleXMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function codeFor(User $user): string
    {
        $mail = Mail::sent(PickleXMail::class, fn ($m) => $m->template === 'reset' && $m->hasTo($user->email))->last();
        $this->assertNotNull($mail);

        return $mail->data['code'];
    }

    public function test_page_and_login_link(): void
    {
        $this->get(route('login'))->assertSee('Forgot password?')->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('Reset your password')->assertSee('Back on court in a minute.');
    }

    public function test_full_reset_with_a_code(): void
    {
        Mail::fake();
        $kai = User::factory()->create(['name' => 'Kai Santos', 'email' => 'kai@picklex.app']);

        $c = Livewire::test(ForgotPassword::class)
            ->call('send')->assertSet('error', 'Enter the email you use for PickleX.')
            ->set('email', 'kai@')->call('send')->assertSet('error', "That email doesn't look right. Check it and try again.")
            ->set('email', 'kai@picklex.app')->call('send')
            ->assertSet('step', 'code')->assertSee('k•••@picklex.app');

        $code = $this->codeFor($kai);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $mail = Mail::sent(PickleXMail::class)->first();
        $this->assertTrue($mail->security);
        $this->assertStringContainsString('Your code is '.$code, $mail->preheader);
        $this->assertStringContainsString(substr($code, 0, 3).' '.substr($code, 3), $mail->render());
        $this->assertFalse(Hash::check($code, '') || DB::table('password_reset_tokens')->where('token', $code)->exists(), 'code must be stored hashed');

        $wrong = $code === '000000' ? '111111' : '000000';
        $c->set('code', '12')->call('verify')->assertSet('error', 'Enter all 6 digits from the email.')
            ->set('code', $wrong)->call('verify')->assertSet('error', "That code doesn't match. Check the newest email from PickleX and try again.")
            ->set('code', $code)->call('verify')->assertSet('step', 'new')
            ->set('password', 'short1')->call('save')->assertSet('error', "Your new password doesn't meet the checklist yet.")
            ->set('password', 'kaiRocks2026')->call('save')->assertSet('error', "Your new password doesn't meet the checklist yet.")
            ->set('password', 'Dinks4days!')->set('confirm', 'Dinks4days')->call('save')->assertSet('error', "The two passwords don't match. Type the new one again.")
            ->set('confirm', 'Dinks4days!')->call('save')->assertSet('step', 'done')->assertSee('Password updated')
            ->call('finish')->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Dinks4days!', $kai->fresh()->password));
        $this->assertFalse(DB::table('password_reset_tokens')->where('email', 'kai@picklex.app')->exists());
    }

    public function test_unknown_email_looks_the_same_but_sends_nothing(): void
    {
        Mail::fake();

        Livewire::test(ForgotPassword::class)->set('email', 'nobody@picklex.app')->call('send')
            ->assertSet('step', 'code')->assertSee("If there's a PickleX account for", false);

        Mail::assertNothingSent();
    }

    public function test_five_wrong_codes_lock_the_code_and_expired_codes_fail(): void
    {
        Mail::fake();
        $kai = User::factory()->create(['email' => 'kai@picklex.app']);
        $c = Livewire::test(ForgotPassword::class)->set('email', 'kai@picklex.app')->call('send');
        $code = $this->codeFor($kai);
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $i) {
            $c->set('code', $wrong)->call('verify');
        }
        $c->assertSet('error', 'Too many tries. Send yourself a new code to keep going.')
            ->set('code', $code)->call('verify')->assertSet('step', 'code');

        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(11)]);
        $c2 = Livewire::withQueryParams(['email' => 'kai@picklex.app', 'code' => $code])->test(ForgotPassword::class)
            ->assertSet('step', 'code')->assertSet('code', $code);
        RateLimiter::clear('pw-tries:'.sha1('kai@picklex.app'));
        $c2->call('verify')->assertSet('error', 'That code has expired. Send yourself a new one.');
    }

    public function test_new_password_needs_a_verified_code(): void
    {
        $kai = User::factory()->create(['email' => 'kai@picklex.app']);
        $before = $kai->password;

        Livewire::test(ForgotPassword::class)->set('email', 'kai@picklex.app')
            ->set('password', 'Dinks4days!')->set('confirm', 'Dinks4days!')->call('save')
            ->assertSet('step', 'email');

        $this->assertSame($before, $kai->fresh()->password);
    }
}
