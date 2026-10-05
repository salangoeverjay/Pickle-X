<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Co-hosts: up to 3 accounts per open play who can start it and run the court board. And who started a live session. */
    public function up(): void
    {
        Schema::create('open_play_cohosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['open_play_id', 'user_id']);
        });
        Schema::table('open_plays', function (Blueprint $table) {
            $table->foreignId('started_by')->nullable()->after('started_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('started_by');
        });
        Schema::dropIfExists('open_play_cohosts');
    }
};
