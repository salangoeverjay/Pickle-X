<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A player in one open play: a joined member, or a walk-in with just a name. */
class Participant extends Model
{
    protected $fillable = ['open_play_id', 'user_id', 'name', 'left_at', 'checked_in_at', 'paid'];

    protected function casts(): array
    {
        return ['left_at' => 'datetime', 'checked_in_at' => 'datetime', 'paid' => 'boolean'];
    }

    public function openPlay(): BelongsTo
    {
        return $this->belongsTo(OpenPlay::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firstName(): string
    {
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

    public function color(): string
    {
        return $this->user?->color() ?? palette_color($this->id);
    }
}
