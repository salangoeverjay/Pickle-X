<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * A What's new update. Admins write it for everyone, players only (members who don't organize) or organizers only.
 * Published updates show once (a popup) to each person in the audience who hasn't read them, then stay on the What's new page.
 */
class News extends Model
{
    protected $table = 'news';

    protected $fillable = ['title', 'body', 'kind', 'audience', 'published_at', 'published_by'];

    // [label, icon path, Tailwind classes for the badge and icon tile].
    public const KINDS = [
        'feature' => ['New feature', 'M12 3.5 13.9 9l5.6 1.9-5.6 1.9L12 18.5l-1.9-5.7-5.6-1.9L10.1 9Z', 'border-lime-soft bg-lime-soft text-lime-text'],
        'enhancement' => ['Enhancement', 'M12 19V5M5.5 11.5 12 5l6.5 6.5', 'border-court bg-court text-white'],
        'fix' => ['Bug fix', 'M14.5 6.5a4 4 0 0 0 5 5L12 19a2.1 2.1 0 0 1-3-3l7.5-7.5a4 4 0 0 0-5-5', 'border-hot-line bg-hot-soft text-hot'],
    ];

    public const AUDIENCES = ['all' => 'Everyone', 'member' => 'Players only', 'organizer' => 'Organizers only'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'news_reads')->withPivot('read_at');
    }

    public function isLive(): bool
    {
        return $this->published_at !== null;
    }

    /** "Today" or "Sep 24". */
    public function dateLabel(): string
    {
        return ! $this->published_at || $this->published_at->isToday() ? 'Today' : $this->published_at->format('M j');
    }

    public function scopeLive(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /** Published updates $user's audience sees, newest first. Admins don't get What's new. */
    public function scopeFor(Builder $query, User $user): void
    {
        $query->live()->whereIn('audience', $user->isAdmin() ? [] : ['all', $user->isOrganizer() ? 'organizer' : 'member'])->latest('published_at')->latest('id');
    }

    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->for($user)->whereDoesntHave('readers', fn ($q) => $q->whereKey($user->id));
    }

    public static function markRead(User $user, iterable $ids): void
    {
        $rows = collect($ids)->map(fn ($id) => ['news_id' => $id, 'user_id' => $user->id, 'read_at' => now()])->all();
        if ($rows) {
            DB::table('news_reads')->insertOrIgnore($rows);
        }
    }

    /** People an audience reaches: active players and organizers (not admins, not suspended). */
    public static function audience(string $audience): Builder
    {
        return User::query()->where('role', '!=', 'admin')
            ->where(fn ($q) => $q->whereNull('suspended_at')->orWhere('suspended_until', '<', now()))
            ->when($audience === 'organizer', fn ($q) => $q->where('organizer_status', 'approved'))
            ->when($audience === 'member', fn ($q) => $q->where(fn ($q) => $q->whereNull('organizer_status')->orWhere('organizer_status', '!=', 'approved')));
    }
}
