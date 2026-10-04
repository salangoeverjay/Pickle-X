<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = ['open_play_id', 'court', 'score_a', 'score_b', 'winner', 'subs', 'started_at', 'played_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'played_at' => 'datetime', 'subs' => 'array'];
    }

    public function openPlay(): BelongsTo
    {
        return $this->belongsTo(OpenPlay::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }
}
