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

        // Organizers can only log in once an admin has approved them.
        $user = Auth::user();
        if ($user->role === 'organizer' && $user->organizer_status !== 'approved') {
            Auth::logout();

            throw ValidationException::withMessages(['email' => $user->organizer_status === 'declined'
                ? 'Your organizer request was not approved. Contact the PickleX team to reapply.'
                : "Your organizer request for {$user->club} is still waiting for admin approval.",
            ]);
        }

        session()->regenerate();

        return $this->redirectIntended(route($user->homeRoute()), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
