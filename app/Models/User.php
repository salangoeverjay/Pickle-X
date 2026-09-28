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

#[Fillable(['name', 'email', 'password', 'role', 'organizer_status', 'club', 'city', 'courts', 'organizer_note', 'phone', 'avatar_color', 'preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Avatar palette offered in settings, [hex => label].
    public const AVATARS = [
        '#FFD3A3' => 'Peach', '#CDEBB0' => 'Mint', '#BCD9F7' => 'Sky', '#F7C9DB' => 'Rose',
        '#E2D2FF' => 'Lilac', '#FFEFA3' => 'Butter', '#BDEBE3' => 'Seafoam', '#FFCBBB' => 'Coral',
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

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Route name of the screen this user lands on after logging in. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.requests',
            'organizer' => 'organizer.plays',
            default => 'member.dashboard',
        };
    }

    public function roleLabel(): string
    {
        return ucfirst($this->role);
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
        return $this->avatar_color ?? match ($this->role) {
            'organizer' => '#FFD3A3',
            'admin' => '#E2D2FF',
            default => '#BCD9F7',
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
