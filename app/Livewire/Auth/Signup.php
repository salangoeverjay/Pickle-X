<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Sign up · PickleX')]
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
            'organizer_status' => 'pending',
            'organizer_note' => $data['note'] ?: null,
        ] : [
            'role' => 'member',
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $this->reset('password');

        if ($organizer) {
            $this->sent = true;

            return;
        }

        Auth::login($user);
        session()->regenerate();

        return $this->redirect(route('member.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.signup');
    }
}
