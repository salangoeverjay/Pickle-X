<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Log in · PickleX')]
class Login extends Component
{
    public const REMEMBERED = 'px_remembered_until';

    public const SUSPENDED = 'This account is suspended. If you think this is a mistake, contact the PickleX team at support@picklex.app.';

    public string $email = '';

    public string $password = '';

    // Stay logged in on this device for 30 days (config/auth.php, guards.web.remember).
    public bool $remember = false;

    public function mount(): void
    {
        // Filled in after a password reset.
        $this->email = (string) session('login_email', '');
    }

    public function login()
    {
        $this->validate(
            ['email' => 'required|string', 'password' => 'required|string'],
            ['required' => 'Enter your email and password to continue.'],
        );

        $key = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

        if (Auth::user()->isSuspended()) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => self::SUSPENDED]);
        }

        // One account, two modes: organizer requests never block playing.
        $user = Auth::user();
        $toast = match ($user->organizer_status) {
            'approved' => "Welcome back, {$user->firstName()}. You can organize and play.",
            'pending' => "Signed in as a player. Your organizer request for {$user->club} is still in review.",
            'declined' => 'Signed in as a player. Your organizer request was not approved.',
            default => null,
        };

        session()->regenerate();
        session()->forget('mode');
        session()->flash('loader', ['kind' => 'login']);
        if ($this->remember) {
            // Laravel's remember cookie doesn't say when it expires; this one does, for Account settings.
            Cookie::queue(self::REMEMBERED, now()->addMinutes(config('auth.guards.web.remember'))->toDateString(), config('auth.guards.web.remember'));
        }
        if ($toast) {
            session()->flash('toast', $toast);
        }

        return $this->redirectIntended(route($user->homeRoute()), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
