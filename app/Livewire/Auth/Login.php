<?php

namespace App\Livewire\Auth;

use App\Models\OpenPlay;
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
            $message = Auth::user()->suspensionMessage();
            Auth::logout();

            throw ValidationException::withMessages(['email' => $message]);
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

    /** Came here from a share link: the open play to join once you're in. */
    private function joining(): ?OpenPlay
    {
        $code = session('join.code');

        return $code ? OpenPlay::where('link_code', $code)->where('status', '!=', 'done')->first() : null;
    }

    public function cancelJoin(): void
    {
        session()->forget(['join', 'url.intended']);
    }

    public function render()
    {
        return view('livewire.auth.login', ['joining' => $this->joining()]);
    }
}
