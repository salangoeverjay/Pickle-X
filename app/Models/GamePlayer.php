<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One player's side of a logged game. Stats queries read this table. */
class GamePlayer extends Model
{
    public $timestamps = false;

    protected $fillable = ['game_id', 'participant_id', 'user_id', 'team', 'won', 'locked'];

    protected function casts(): array
    {
        return ['won' => 'boolean', 'locked' => 'boolean'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
