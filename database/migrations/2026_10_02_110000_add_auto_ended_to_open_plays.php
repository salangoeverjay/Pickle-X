<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            // Ended by PickleX 12 hours after its end time because nobody tapped End session.
            $table->boolean('auto_ended')->default(false)->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropColumn('auto_ended');
        });
    }
};
