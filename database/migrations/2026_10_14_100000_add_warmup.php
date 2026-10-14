<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Minutes each new foursome warms up before the game clock starts (0 = off), and how long each game's warmup ran. */
    public function up(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->unsignedTinyInteger('warmup')->default(3)->after('rotation');
        });
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedSmallInteger('warmup_seconds')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', fn (Blueprint $table) => $table->dropColumn('warmup'));
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('warmup_seconds'));
    }
};
