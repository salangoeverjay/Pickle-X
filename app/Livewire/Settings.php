<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\Looks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Account settings · PickleX')]
class Settings extends Component
{
    // Profile
    public string $name = '';

    public string $email = '';

    // Optional nickname, and which name other players see: "full" or "display".
    public string $displayName = '';

    public string $showAs = 'full';

    public string $phone = '';

    public string $club = '';

    // Password
    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Organizer defaults for new open plays
    public int $courts = 4;

    public $raceTo = 11;

    public string $rotation = 'rotate';

    public bool $confirmingDelete = false;

    // "Organize open plays" request (players in member mode)
    public string $applyClub = '';

    public string $applyCity = '';

    public ?string $applyError = null;

    // Your look: which picker shows (border|queue), and whether a pick has been saved this visit.
    public string $lookTab = 'border';

    public bool $lookSaved = false;

    public function mount()
    {
        $user = $this->user();

        $this->fill([
            'name' => $user->name,
            'displayName' => (string) $user->display_name,
            'showAs' => $user->usesDisplayName() ? 'display' : 'full',
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'club' => (string) $user->club,
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
        $this->displayName = trim(preg_replace('/\s+/', ' ', $this->displayName));
        $this->showAs = $this->showAs === 'display' ? 'display' : 'full';

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'club' => 'nullable|string|max:255',
            'displayName' => ['nullable', 'string', 'max:20', Rule::requiredIf($this->showAs === 'display')],
        ], [
            'displayName.max' => 'Keep your display name to 20 characters or fewer.',
            'displayName.required' => 'Add a display name, or choose to show your full name.',
            'name.required' => 'Your name cannot be empty.',
            'email.required' => 'Enter a valid email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'Another account already uses that email address.',
            'phone.max' => 'That mobile number is too long.',
        ]);

        $user->fill([
            'name' => $data['name'],
            'display_name' => $this->displayName ?: null,
            'show_display_name' => $this->showAs === 'display',
            'email' => $data['email'],
            'phone' => trim($data['phone'] ?? '') ?: null,
        ]);

        // Organizer club names (approved or under review) change through admin review, not here.
        if ($this->homeClubEditable($user)) {
            $user->club = trim($data['club'] ?? '') ?: null;
        }

        $user->save();

        $this->dispatch('toast', message: $user->usesDisplayName() ? "Profile saved · others see you as {$user->display_name}" : 'Profile saved');
    }

    // ---------------------------------------------------------------- profile picture
    // Called from the picker dialog (resources/js/avatar-picker.js); each returns ['ok' => bool, 'error' => ?string].

    public function usePreset(string $id): array
    {
        abort_unless(isset(User::AVATAR_PRESETS[$id]), 422);

        return $this->setPicture("preset:{$id}", null, 'Avatar updated: '.User::AVATAR_PRESETS[$id]);
    }

    public function useInitials(string $color): array
    {
        abort_unless(isset(User::AVATARS[$color]), 422);

        return $this->setPicture(null, $color, 'Showing your initials');
    }

    /** $dataUrl: the 256×256 JPEG the picker cropped in the browser. */
    public function usePhoto(string $dataUrl): array
    {
        $bytes = preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/]+={0,2})$#', $dataUrl, $m) ? base64_decode($m[1], true) : false;
        $info = $bytes && strlen($bytes) <= 400_000 ? @getimagesizefromstring($bytes) : false;
        if (! $info || $info[2] !== IMAGETYPE_JPEG || $info[0] !== 256 || $info[1] !== 256) {
            return ['ok' => false, 'error' => "We couldn't process that photo. Try another one."];
        }

        // Re-encode so only clean pixel data is stored and served.
        if (function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($bytes))) {
            ob_start();
            imagejpeg($img, null, 88);
            $bytes = ob_get_clean();
            imagedestroy($img);
        }

        DB::table('avatar_photos')->updateOrInsert(['user_id' => $this->user()->id], ['data' => base64_encode($bytes), 'created_at' => now(), 'updated_at' => now()]);

        return $this->setPicture('photo:'.substr(sha1($bytes), 0, 12), null, 'Profile photo updated', keepPhoto: true);
    }

    public function removePicture(): void
    {
        $this->setPicture(null, null, 'Picture removed. Your initials are showing again.');
    }

    private function setPicture(?string $avatar, ?string $color, string $toast, bool $keepPhoto = false): array
    {
        $user = $this->user();
        $user->forceFill(['avatar' => $avatar] + ($color ? ['avatar_color' => $color] : []))->save();
        if (! $keepPhoto) {
            DB::table('avatar_photos')->where('user_id', $user->id)->delete();
        }
        $this->dispatch('toast', message: $toast);

        return ['ok' => true, 'error' => null, 'avatar' => $avatar, 'url' => $user->avatarUrl(), 'color' => $user->color()];
    }

    /** Wear an avatar border (kind "frame") or queue background (kind "queue"). An empty id removes it. Saves right away. */
    public function setLook(string $kind, string $id = ''): void
    {
        abort_unless(in_array($kind, ['frame', 'queue']), 404);
        $user = $this->user();
        if ($id !== '' && ! Looks::valid($kind, $id)) {
            return;
        }
        if (isset(Looks::EXCLUSIVE[$id]) && ! $user->is_developer) {
            $this->dispatch('toast', message: 'Developer accounts only · an admin unlocks this');

            return;
        }
        $user->forceFill([$kind === 'frame' ? 'frame' : 'queue_bg' => $id ?: null])->save();
        $this->lookSaved = true;
        $label = Looks::label($kind, $id);
        $this->dispatch('toast', message: $kind === 'frame'
            ? ($label ? "Avatar border: {$label}" : 'Avatar border removed')
            : ($label ? "Queue background: {$label}" : 'Queue background removed'));
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

        $this->dispatch('toast', message: 'Defaults saved. New open plays will start with them.');
    }

    /** A player asks to host open plays on this same account. */
    public function requestOrganizer()
    {
        $user = $this->user();
        abort_unless($user->mode() === 'member' && ! in_array($user->organizer_status, ['pending', 'approved']), 403);

        $club = trim($this->applyClub);
        $city = trim($this->applyCity);
        if ($club === '' || $city === '' || mb_strlen($club) > 255 || mb_strlen($city) > 255) {
            $this->applyError = 'Add your club name and city.';

            return;
        }

        $user->update([
            'organizer_status' => 'pending',
            'club' => $club,
            'city' => $city,
            'courts' => $user->courts ?: 2,
            'organizer_note' => 'Applied from an existing player account.',
        ]);
        $this->club = $club;
        $this->reset('applyClub', 'applyCity', 'applyError');

        $this->dispatch('toast', message: 'Organizer request sent. You keep playing on the same account.');
    }

    protected function homeClubEditable(User $user): bool
    {
        return $user->mode() === 'member' && ! in_array($user->organizer_status, ['pending', 'approved']);
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

    /** Switches this mode can see, [pref key => [label, hint]]. */
    protected function switches(User $user): array
    {
        return match ($user->mode()) {
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
                'notify.sessionRecap' => ['Recap after each open play', 'Your record, your games and the final standings'],
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

        $picker = [
            'current' => ['avatar' => $user->avatar, 'url' => $user->avatarUrl(), 'color' => $user->color()],
            'presets' => collect(User::AVATAR_PRESETS)->map(fn ($label, $id) => ['label' => $label, 'url' => asset("avatars/presets/{$id}.svg")]),
            'groups' => User::AVATAR_GROUPS,
            // preset id => group key, so the picker opens on the group of your current avatar.
            'groupOf' => collect(User::AVATAR_GROUPS)->flatMap(fn ($g, $key) => array_fill_keys($g[2], $key)),
            'colors' => User::AVATARS,
        ];

        return view('livewire.settings', [
            'picker' => $picker,
            'user' => $user,
            'notifications' => $switches->filter(fn ($s, $key) => str_starts_with($key, 'notify.')),
            'privacy' => $switches->filter(fn ($s, $key) => str_starts_with($key, 'privacy.')),
            'mode' => $user->mode(),
            'homeClubEditable' => $this->homeClubEditable($user),
            'deleteHint' => match ($user->mode()) {
                'organizer' => "Removes your organizer access. Past sessions stay with your club's history.",
                'admin' => 'Removes your admin access to PickleX.',
                default => 'Removes your profile, stats and queue history.',
            },
            'sections' => array_filter([
                ['profile', 'Profile', 'user'],
                ['security', 'Password', 'lock'],
                match ($user->mode()) {
                    'organizer' => ['club', 'Club defaults', 'court'],
                    'member' => ['host', 'Organize', 'court'],
                    default => null,
                },
                ['notify', 'Notifications', 'bell'],
                ['look', 'Appearance', 'moon'],
                $user->mode() === 'member' ? ['privacy', 'Privacy', 'shield'] : null,
                ['danger', 'Account', 'logout'],
            ]),
        ]);
    }
}
