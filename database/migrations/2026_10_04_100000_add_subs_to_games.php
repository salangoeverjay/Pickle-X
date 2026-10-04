<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Subs made mid-game on a live court: [{out, in, at}], so Latest results can note them. */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->json('subs')->nullable()->after('winner');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('subs');
        });
    }
};
