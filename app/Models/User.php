<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Looks;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys;

#[Fillable(['name', 'email', 'password', 'role', 'organizer_status', 'club', 'city', 'courts', 'organizer_note', 'phone', 'avatar_color', 'avatar', 'preferences', 'display_name', 'show_display_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasPasskeys
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithPasskeys, Notifiable;

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
            'joins' => true, 'payClaims' => true, 'reminder' => true, 'clubRecap' => false,
            'requests' => true, 'adminRecap' => false,
        ],
        'privacy' => ['ranked' => true, 'statsPublic' => true],
        'defaults' => ['courts' => 4, 'race_to' => 11, 'rotation' => 'rotate', 'warmup' => 3],
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
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
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
     * Why an admin suspended an account: [label, picker hint, phrase for "suspended for ...", note placeholder,
     * the email's reason title, the email's line on why it matters].
     */
    public const SUSPENSION_REASONS = [
        'noshow' => ['No-shows', "Joins open plays and doesn't turn up", 'repeated no-shows', 'e.g. You missed 3 of your last 4 open plays without leaving them first.',
            'Repeated no-shows at open plays you joined', 'Organizers hold a spot for every player who joins, so missing several in a row keeps others off the courts.'],
        'unpaid' => ['Unpaid fees', 'Owes organizers for open plays', 'unpaid open play fees', 'e.g. You still owe ₱360 for three Friday Socials. Settle it with the organizer.',
            'Unpaid open play fees', 'Organizers pay for the courts up front, so fees for the open plays you join need to be settled.'],
        'conduct' => ['Unsportsmanlike conduct', 'Abuse, threats or harassment', 'unsportsmanlike conduct', "e.g. Two players reported you shouting at them during Sunday's open play.",
            'Unsportsmanlike conduct', "Abuse, threats or harassment of players or organizers aren't allowed at open plays on PickleX."],
        'fake' => ['Fake or duplicate account', 'Not a real person, or a second account', 'a fake or duplicate account', 'e.g. This is a second account. Please use the one you joined with first.',
            'Fake or duplicate account', 'Each player gets one account in their own name, so queues, rankings and payments stay fair.'],
        'spam' => ['Spam or scams', 'Selling, scams or spam in open plays', 'spam or scams', 'e.g. You posted paddle sale links in three open plays.',
            'Spam or scams', "Selling, scams and spam aren't allowed on PickleX: not in display names, open plays or anywhere else."],
        'other' => ['Something else', 'Explain it in the note', 'breaking the community guidelines', 'Say what happened and what they can do next.',
            'Breaking the community guidelines', "This one doesn't fit a usual reason, so the admin explains it below."],
    ];

    /** A timed suspension stops counting once its end date passes, even before users:lift-suspensions clears it. */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null && ! $this->suspended_until?->isPast();
    }

    /** The admin who suspended this account. */
    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /** SUSPENSION_REASONS row for this account (older suspensions without one count as "other"). */
    public function suspensionReason(): array
    {
        return self::SUSPENSION_REASONS[$this->suspension_reason] ?? self::SUSPENSION_REASONS['other'];
    }

    /**
     * $days: 7 or 30, or null until an admin restores it. Lifts on its own at the start of the end day.
     * Spam often lives in the display name, so a spam suspension turns it off (others see the full name).
     */
    public function suspend(string $reason, ?int $days, ?string $note, User $by): void
    {
        $this->forceFill([
            'suspended_at' => now(), 'suspension_reason' => $reason, 'suspension_note' => $note ?: null,
            'suspended_until' => $days ? today()->addDays($days) : null, 'suspended_by' => $by->id,
        ] + ($reason === 'spam' ? ['show_display_name' => false] : []))->save();
        $this->signOutEverywhere();
        $this->releaseUpcomingSpots();
    }

    public function liftSuspension(): void
    {
        $this->forceFill(['suspended_at' => null, 'suspension_reason' => null, 'suspension_note' => null, 'suspended_until' => null, 'suspended_by' => null])->save();
    }

    /** Shown on the log in screen (password or passkey). */
    public function suspensionMessage(): string
    {
        $why = $this->suspensionReason()[2];

        return 'This account is suspended '.($this->suspended_until ? 'until '.$this->suspended_until->format('D, M j')." for {$why}" : "for {$why} until an admin restores it")
            .". We emailed {$this->email} the details. Reply to that email if you think it's a mistake.";
    }

    /** One of member | organizer | admin, as Admin › Users shows and sets it. */
    public function accountRole(): string
    {
        return match (true) {
            $this->isAdmin() => 'admin',
            $this->isOrganizer() => 'organizer',
            default => 'member',
        };
    }

    /** Ends every session and invalidates "Remember me" cookies (passkeys stay). */
    public function signOutEverywhere(): void
    {
        DB::table('sessions')->where('user_id', $this->id)->delete();
        $this->forceFill(['remember_token' => Str::random(60)])->save();
    }

    /** Frees the spots this account holds in upcoming open plays (suspended or deleted accounts). */
    public function releaseUpcomingSpots(): void
    {
        OpenPlay::status('upcoming')->whereHas('activeParticipants', fn ($q) => $q->where('user_id', $this->id))->get()->each->leave($this);
    }

    /** Deletes the account. Games stay in other players' history as "Former player". */
    public function deleteAccount(): void
    {
        DB::transaction(function () {
            $this->releaseUpcomingSpots();
            Participant::where('user_id', $this->id)->update(['name' => 'Former player']);
            DB::table('sessions')->where('user_id', $this->id)->delete();
            $this->delete();
        });
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
        return Looks::cls('frame', $this->frame, (bool) $this->is_developer, $this->sponsor_tier);
    }

    /** Queue background classes for this member's spot in line, or '' for none. */
    public function queueBgClass(): string
    {
        return Looks::cls('queue', $this->queue_bg, (bool) $this->is_developer, $this->sponsor_tier);
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
