<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A player in one open play: a joined member, or a walk-in with just a name. */
class Participant extends Model
{
    protected $fillable = ['open_play_id', 'user_id', 'name', 'left_at', 'checked_in_at', 'paid', 'from_list'];

    protected function casts(): array
    {
        return ['left_at' => 'datetime', 'checked_in_at' => 'datetime', 'paid' => 'boolean', 'from_list' => 'boolean'];
    }

    public function openPlay(): BelongsTo
    {
        return $this->belongsTo(OpenPlay::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Linked players show the name they chose for other players (User::shownName()); guests keep the typed name. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (?string $value) => $this->user_id && $this->user ? $this->user->shownName() : $value);
    }

    /** The account's full name (for organizers), or the guest's name. */
    public function fullName(): string
    {
        return $this->user?->name ?? $this->name;
    }

    public function firstName(): string
    {
        if ($this->user?->usesDisplayName()) {
            return $this->user->display_name;
        }

        return strtok(trim($this->name), ' ') ?: $this->name;
    }

    public function initials(): string
    {
        return initials($this->name);
    }

    public function avatarUrl(): ?string
    {
        return $this->user?->avatarUrl();
    }

    public function frameClass(): string
    {
        return $this->user?->frameClass() ?? '';
    }

    public function queueBgClass(): string
    {
        return $this->user?->queueBgClass() ?? '';
    }

    public function color(): string
    {
        return $this->user?->color() ?? palette_color($this->id);
    }
}
