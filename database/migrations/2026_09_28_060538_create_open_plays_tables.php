<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('club');
            $table->string('venue');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedTinyInteger('courts');
            $table->unsignedTinyInteger('cap');
            $table->unsignedTinyInteger('race_to')->default(11);
            $table->string('rotation')->default('rotate'); // rotate | winners
            $table->string('visibility')->default('public'); // public | invite
            $table->string('fee')->nullable();
            $table->string('status')->default('upcoming'); // upcoming | live | done
            $table->boolean('auto_fill')->default(true);
            // Live court board: queue entries and courts. See OpenPlay for the shape.
            $table->json('board')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });

        // Everyone in a session: joined members and organizer-added walk-ins (no user).
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->dateTime('left_at')->nullable();
            $table->timestamps();

            $table->unique(['open_play_id', 'user_id']);
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('court');
            $table->unsignedTinyInteger('score_a');
            $table->unsignedTinyInteger('score_b');
            $table->char('winner', 1); // a | b
            $table->dateTime('started_at');
            $table->dateTime('played_at');
            $table->timestamps();
        });

        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('team', 1); // a | b
            $table->boolean('won');
            $table->boolean('locked')->default(false);

            $table->index(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
        Schema::dropIfExists('games');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('open_plays');
    }
};
