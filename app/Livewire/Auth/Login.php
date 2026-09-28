<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Log in · PickleX')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

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

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

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
