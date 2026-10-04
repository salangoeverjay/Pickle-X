<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            // When the organizer's one-hour "Session reminder" email went out (sent once per open play).
            $table->timestamp('reminder_sent_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
