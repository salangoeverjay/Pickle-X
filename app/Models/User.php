<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'organizer_status', 'club', 'city', 'courts', 'organizer_note', 'phone', 'avatar_color', 'avatar', 'preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Mirror the column default so new, unsaved-refresh models know they're players.
    protected $attributes = ['role' => 'member'];

    // Avatar palette offered in settings, [hex => label].
    public const AVATARS = [
        '#FFD3A3' => 'Peach', '#CDEBB0' => 'Mint', '#BCD9F7' => 'Sky', '#F7C9DB' => 'Rose',
        '#E2D2FF' => 'Lilac', '#FFEFA3' => 'Butter', '#BDEBE3' => 'Seafoam', '#FFCBBB' => 'Coral',
    ];

    // PickleX avatars (public/avatars/presets/{id}.svg), [id => label].
    public const AVATAR_PRESETS = [
        'paddle' => 'Paddle', 'ball' => 'Pickleball', 'happy' => 'Happy ball', 'dill' => 'Big dill',
        'dink' => 'Dink shot', 'kitchen' => 'Kitchen', 'doubles' => 'Doubles', 'ace' => 'Ace',
        'champ' => 'Champion', 'streak' => 'Hot streak', 'shoes' => 'Court shoes', 'night' => 'Night owl',
    ];

    public const DEFAULT_PREFERENCES = [
        'notify' => [
            'upNext' => true, 'liveStart' => true, 'newPlays' => true, 'recap' => false,
            'joins' => true, 'courtIdle' => true, 'reminder' => true, 'clubRecap' => false,
            'requests' => true, 'adminRecap' => false,
        ],
        'privacy' => ['ranked' => true, 'statsPublic' => true],
        'defaults' => ['courts' => 4, 'race_to' => 11, 'rotation' => 'rotate'],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function openPlays(): HasMany
    {
        return $this->hasMany(OpenPlay::class, 'organizer_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /** Players. Organizers are members too: one account, two modes. */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /** Has approved organizer access (can host open plays). */
    public function isOrganizer(): bool
    {
        return $this->isMember() && $this->organizer_status === 'approved';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The mode the app is showing: admin, organizer or member (player).
     * Approved organizers default to organizer mode; routes set it via EnsureRole.
     */
    public function mode(): string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }

        return $this->isOrganizer() && session('mode', 'organizer') === 'organizer' ? 'organizer' : 'member';
    }

    /** Route name of the home screen for the current mode. */
    public function homeRoute(): string
    {
        return match ($this->mode()) {
            'admin' => 'admin.requests',
            'organizer' => 'organizer.plays',
            default => 'member.dashboard',
        };
    }

    public function roleLabel(): string
    {
        return ucfirst($this->mode());
    }

    public function firstName(): string
    {
        return strtok(trim($this->name), ' ') ?: $this->name;
    }

    public function initials(): string
    {
        return initials($this->name);
    }

    public function color(): string
    {
        return $this->avatar_color ?? ($this->isAdmin() ? '#E2D2FF' : ($this->organizer_status ? '#FFD3A3' : '#BCD9F7'));
    }

    /** Profile picture URL, or null when showing initials on color(). */
    public function avatarUrl(): ?string
    {
        [$kind, $value] = explode(':', (string) $this->avatar, 2) + [null, null];

        return match ($kind) {
            'preset' => isset(self::AVATAR_PRESETS[$value]) ? asset("avatars/presets/{$value}.svg") : null,
            'photo' => route('avatar.photo', ['user' => $this->id, 'version' => $value]),
            default => null,
        };
    }

    /** "Your photo", "PickleX avatar · Dink shot", or null for initials. */
    public function avatarLabel(): ?string
    {
        [$kind, $value] = explode(':', (string) $this->avatar, 2) + [null, null];

        return match ($kind) {
            'preset' => 'PickleX avatar · '.(self::AVATAR_PRESETS[$value] ?? 'Avatar'),
            'photo' => 'Your photo',
            default => null,
        };
    }

    /** Read a preference with dot notation, e.g. pref('privacy.ranked'). */
    public function pref(string $key): mixed
    {
        return data_get($this->preferences, $key, data_get(self::DEFAULT_PREFERENCES, $key));
    }

    public function setPref(string $key, mixed $value): void
    {
        $prefs = array_replace_recursive(self::DEFAULT_PREFERENCES, $this->preferences ?? []);
        data_set($prefs, $key, $value);
        $this->preferences = $prefs;
    }
}
