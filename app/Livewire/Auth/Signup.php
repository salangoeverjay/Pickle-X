<?php

namespace App\Livewire\Auth;

use App\Models\OpenPlay;
use App\Models\User;
use App\Support\Emails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Sign up | PickleX')]
class Signup extends Component
{
    #[Url]
    public string $role = 'member';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $club = '';

    public string $city = '';

    public int $courts = 4;

    public string $note = '';

    // Organizer requests end on a "Request sent" screen instead of logging in.
    public bool $sent = false;

    public function mount()
    {
        if (! in_array($this->role, ['member', 'organizer'])) {
            $this->role = 'member';
        }
        // From a share link: a guest's name and email carry over.
        if (session('join.code')) {
            $this->role = 'member';
            $this->name = (string) session('join.name', '');
            $this->email = (string) session('join.email', '');
        }
    }

    public function signup()
    {
        $organizer = $this->role === 'organizer';

        $data = $this->validate([
            'role' => ['required', Rule::in(['member', 'organizer'])],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'club' => [Rule::requiredIf($organizer), 'nullable', 'string', 'max:255'],
            'city' => [Rule::requiredIf($organizer), 'nullable', 'string', 'max:255'],
            'courts' => 'integer|between:1,20',
            'note' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'Add your name, email and a password.',
            'email.required' => 'Add your name, email and a password.',
            'password.required' => 'Add your name, email and a password.',
            'email.email' => 'That email address does not look right.',
            'email.unique' => 'An account with that email already exists. Try logging in.',
            'club.required' => 'Add your club name and city so the admin can review it.',
            'city.required' => 'Add your club name and city so the admin can review it.',
        ]);

        $user = User::create($organizer ? [
            ...collect($data)->except('note'),
            'role' => 'member', // organizer access is organizer_status, on this same player account
            'organizer_status' => 'pending',
            'organizer_note' => $data['note'] ?: null,
        ] : [
            'role' => 'member',
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $this->reset('password');
        Emails::send($user, Emails::welcome($user));

        // Everyone can play right away; organizer tools unlock on this account once approved.
        Auth::login($user);
        session()->regenerate();

        if ($organizer) {
            Emails::notify(User::where('role', 'admin')->get(), 'requests', fn () => Emails::organizerRequest($user));
            // Shown after "Start playing" (a plain link: this guest-only page can't run actions once signed in).
            session()->flash('toast', "You're in as a player. Organizer tools unlock once you're approved.");
            $this->sent = true;
            $this->dispatch('px-loading', kind: 'signup-org', first: $user->firstName());

            return;
        }

        session()->flash('loader', ['kind' => 'signup', 'first' => $user->firstName()]);

        // From a share link: back to it, where the new account joins (and takes over any guest spot).
        return $this->redirect(session('join.code') ? route('join', session('join.code')) : route('member.dashboard'), navigate: true);
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
        return view('livewire.auth.signup', ['joining' => $this->joining()]);
    }
}
