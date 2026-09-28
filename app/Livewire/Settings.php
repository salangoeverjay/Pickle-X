<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Account settings · PickleX')]
class Settings extends Component
{
    // Profile
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $club = '';

    public string $avatar = '';

    // Password
    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Organizer defaults for new open plays
    public int $courts = 4;

    public $raceTo = 11;

    public string $rotation = 'rotate';

    public bool $confirmingDelete = false;

    public function mount()
    {
        $user = $this->user();

        $this->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'club' => (string) $user->club,
            'avatar' => $user->color(),
            'courts' => (int) $user->pref('defaults.courts'),
            'raceTo' => (int) $user->pref('defaults.race_to'),
            'rotation' => $user->pref('defaults.rotation'),
        ]);
    }

    public function saveProfile()
    {
        $user = $this->user();

        $this->name = trim($this->name);
        $this->email = trim($this->email);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'club' => 'nullable|string|max:255',
            'avatar' => ['required', Rule::in(array_keys(User::AVATARS))],
        ], [
            'name.required' => 'Your name cannot be empty.',
            'email.required' => 'Enter a valid email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'Another account already uses that email address.',
            'phone.max' => 'That mobile number is too long.',
            'avatar.*' => 'Pick one of the avatar colors.',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => trim($data['phone'] ?? '') ?: null,
            'avatar_color' => $data['avatar'],
        ]);

        // Organizer club names change through admin review, not here.
        if ($user->isMember()) {
            $user->club = trim($data['club'] ?? '') ?: null;
        }

        $user->save();

        $this->dispatch('toast', message: 'Profile saved');
    }

    public function savePassword()
    {
        $data = $this->validate([
            'currentPassword' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed|different:currentPassword',
        ], [
            'currentPassword.required' => 'Enter your current password.',
            'currentPassword.current_password' => 'That is not your current password.',
            'password.required' => 'Your new password needs at least 8 characters.',
            'password.min' => 'Your new password needs at least 8 characters.',
            'password.confirmed' => "The new passwords don't match.",
            'password.different' => 'Pick a new password that is different from the current one.',
        ]);

        $this->user()->update(['password' => $data['password']]);
        $this->reset('currentPassword', 'password', 'password_confirmation');

        $this->dispatch('toast', message: 'Password updated');
    }

    public function saveDefaults()
    {
        $user = $this->user();
        abort_unless($user->isOrganizer(), 403);

        $this->validate([
            'courts' => 'required|integer|between:1,12',
            'raceTo' => 'required|in:11,15,21',
            'rotation' => 'required|in:rotate,winners',
        ]);

        $user->setPref('defaults.courts', $this->courts);
        $user->setPref('defaults.race_to', (int) $this->raceTo);
        $user->setPref('defaults.rotation', $this->rotation);
        $user->save();

        $this->dispatch('toast', message: 'Defaults saved — new open plays will start with them');
    }

    /** Flip a notification or privacy switch. Saves right away. */
    public function toggle(string $key)
    {
        $user = $this->user();
        abort_unless(array_key_exists($key, $this->switches($user)), 403);

        $user->setPref($key, ! $user->pref($key));
        $user->save();
    }

    public function deleteAccount()
    {
        $user = $this->user();

        Auth::logout();
        $user->delete();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('toast', 'Account deleted');

        return $this->redirect(route('home'));
    }

    /** Switches this role can see, [pref key => [label, hint]]. */
    protected function switches(User $user): array
    {
        return match ($user->role) {
            'organizer' => [
                'notify.joins' => ['Someone joins my open play', 'Includes the updated player count'],
                'notify.courtIdle' => ['A court is open with players waiting', 'A nudge to fill it from the queue'],
                'notify.reminder' => ['Session reminder', 'One hour before each open play starts'],
                'notify.clubRecap' => ['Weekly club summary', 'Sessions run, games logged and busiest nights'],
            ],
            'admin' => [
                'notify.requests' => ['New organizer request', 'As soon as someone applies to host'],
                'notify.adminRecap' => ['Weekly platform summary', 'New members, organizers and sessions'],
            ],
            default => [
                'notify.upNext' => ["I'm up next", 'When you are in the next group to play'],
                'notify.liveStart' => ['An open play I joined goes live', 'So you know when to head to the courts'],
                'notify.newPlays' => ['New open plays from my clubs', 'Get first pick of the spots'],
                'notify.recap' => ['Weekly stats recap', 'Games, win rate and ranking moves every Monday'],
                'privacy.ranked' => ['Show me in club rankings', 'Turn off to hide your row from other members'],
                'privacy.statsPublic' => ['Let others see who I play with', 'Your partners and opponents list'],
            ],
        };
    }

    protected function user(): User
    {
        return Auth::user();
    }

    public function render()
    {
        $user = $this->user();
        $switches = collect($this->switches($user))->map(fn ($s, $key) => [...$s, 'on' => (bool) $user->pref($key)]);

        return view('livewire.settings', [
            'user' => $user,
            'notifications' => $switches->filter(fn ($s, $key) => str_starts_with($key, 'notify.')),
            'privacy' => $switches->filter(fn ($s, $key) => str_starts_with($key, 'privacy.')),
            'deleteHint' => match ($user->role) {
                'organizer' => "Removes your organizer access. Past sessions stay with your club's history.",
                'admin' => 'Removes your admin access to PickleX.',
                default => 'Removes your profile, stats and queue history.',
            },
            'sections' => array_filter([
                ['profile', 'Profile', 'user'],
                ['security', 'Password', 'lock'],
                $user->isOrganizer() ? ['club', 'Club defaults', 'court'] : null,
                ['notify', 'Notifications', 'bell'],
                ['look', 'Appearance', 'moon'],
                $user->isMember() ? ['privacy', 'Privacy', 'shield'] : null,
                ['danger', 'Account', 'logout'],
            ]),
        ]);
    }
}
