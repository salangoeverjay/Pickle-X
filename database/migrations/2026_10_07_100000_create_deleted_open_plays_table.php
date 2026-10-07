<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A note of each concluded open play an admin deleted (the open play and its games are gone). */
    public function up(): void
    {
        Schema::create('deleted_open_plays', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('club')->nullable();
            $table->string('host')->nullable();
            $table->dateTime('starts_at');
            $table->unsignedInteger('games');
            $table->unsignedInteger('players');
            $table->string('reason', 10);
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_open_plays');
    }
};
