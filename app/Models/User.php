<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Looks;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'organizer_status', 'club', 'city', 'courts', 'organizer_note', 'phone', 'avatar_color', 'avatar', 'preferences', 'display_name', 'show_display_name'])]
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

    /** PickleX avatars (public/avatars/presets/{id}.svg), in picker order. */
    public const AVATAR_PRESETS = [
        // Pickleball
        'paddle' => 'Paddle', 'ball' => 'Pickleball', 'happy' => 'Happy ball', 'dill' => 'Big dill',
        'dink' => 'Dink shot', 'kitchen' => 'Kitchen', 'doubles' => 'Doubles', 'ace' => 'Ace', 'champ' => 'Champion',
        'streak' => 'Hot streak', 'shoes' => 'Court shoes', 'night' => 'Night owl',
        // Smileys
        'grin' => 'Grinning', 'wink' => 'Winky', 'shades' => 'Too cool', 'starstruck' => 'Star-struck',
        'lol' => 'Laughing', 'gameface' => 'Game face',
        // Animals
        'cat' => 'Cat', 'dog' => 'Pup', 'fox' => 'Fox', 'panda' => 'Panda', 'frog' => 'Frog', 'penguin' => 'Penguin',
        // Food
        'mango' => 'Mango', 'pineapple' => 'Pineapple', 'avocado' => 'Avocado', 'watermelon' => 'Watermelon',
        'donut' => 'Donut', 'boba' => 'Milk tea',
        // Nature
        'sunny' => 'Sunny', 'cactus' => 'Cactus', 'wave' => 'Wave', 'palm' => 'Island', 'rainbow' => 'Rainbow',
        'blossom' => 'Blossom',
        // Things
        'crown' => 'Crown', 'rocket' => 'Rocket', 'bolt' => 'Bolt', 'heart' => 'Heart', 'gem' => 'Gem',
        'headphones' => 'Headphones',
    ];

    /** Emoji-style groups for the avatar picker: key => [label, 24px icon path, preset ids]. */
    public const AVATAR_GROUPS = [
        'pb' => ['Pickleball', 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM9 8.5h.01M14.5 8.8h.01M12 12.3h.01M8.3 14.6h.01M15.4 14.8h.01', ['paddle', 'ball', 'happy', 'dill', 'dink', 'kitchen', 'doubles', 'ace', 'champ', 'streak', 'shoes', 'night']],
        'faces' => ['Smileys', 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM8.3 14.2c2 2.3 5.4 2.3 7.4 0M9 9.6h.01M15 9.6h.01', ['grin', 'wink', 'shades', 'starstruck', 'lol', 'gameface']],
        'animals' => ['Animals', 'M5.2 10.2 5.8 4l4.3 3.1h3.8L18.2 4l.6 6.2c.8 1.1 1.2 2.3 1.2 3.6 0 3.9-3.6 6.2-8 6.2s-8-2.3-8-6.2c0-1.3.4-2.5 1.2-3.6ZM9.4 13h.01M14.6 13h.01M11 16.2h2', ['cat', 'dog', 'fox', 'panda', 'frog', 'penguin']],
        'food' => ['Food', 'M6 8.5h12l-1.4 11a1.6 1.6 0 0 1-1.6 1.5H9a1.6 1.6 0 0 1-1.6-1.5ZM5 8.5h14M12 8.5 14.6 3H17M9.5 16.5h.01M12.5 17h.01M14.5 15.5h.01', ['mango', 'pineapple', 'avocado', 'watermelon', 'donut', 'boba']],
        'nature' => ['Nature', 'M5 19.5c0-8.5 5-14.5 14.5-14.5 0 9.3-6 14.5-14.5 14.5ZM5 19.5l7.5-7.5', ['sunny', 'cactus', 'wave', 'palm', 'rainbow', 'blossom']],
        'things' => ['Things', 'M12 3.2l2.3 5.2 5.5.7-4.1 3.8 1.1 5.5-4.8-2.8-4.8 2.8 1.1-5.5-4.1-3.8 5.5-.7Z', ['crown', 'rocket', 'bolt', 'heart', 'gem', 'headphones']],
    ];

    public const DEFAULT_PREFERENCES = [
        'notify' => [
            'upNext' => true, 'liveStart' => true, 'newPlays' => true, 'sessionRecap' => true, 'recap' => false,
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
            'is_developer' => 'boolean',
            'show_display_name' => 'boolean',
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

    /** Other players see the display name (queues, courts, rankings, open plays) instead of the full name. */
    public function usesDisplayName(): bool
    {
        return $this->show_display_name && trim((string) $this->display_name) !== '';
    }

    /** The name other players see. Organizers still see the full name next to the email. */
    public function shownName(): string
    {
        return $this->usesDisplayName() ? $this->display_name : $this->name;
    }

    /** Short form for pairs and toasts: the display name, or the first name. */
    public function shownFirst(): string
    {
        return $this->usesDisplayName() ? $this->display_name : $this->firstName();
    }

    public function initials(): string
    {
        return initials($this->name);
    }

    public function color(): string
    {
        return $this->avatar_color ?? ($this->isAdmin() ? '#E2D2FF' : ($this->organizer_status ? '#FFD3A3' : '#BCD9F7'));
    }

    /** Avatar border classes (resources/css/looks.css), or '' for none. */
    public function frameClass(): string
    {
        return Looks::cls('frame', $this->frame, (bool) $this->is_developer);
    }

    /** Queue background classes for this member's spot in line, or '' for none. */
    public function queueBgClass(): string
    {
        return Looks::cls('queue', $this->queue_bg, (bool) $this->is_developer);
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
