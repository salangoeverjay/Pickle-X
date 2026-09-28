<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One account, two modes: organizers are members with approved organizer access.
        DB::table('users')->where('role', 'organizer')->update(['role' => 'member']);

        Schema::table('participants', function (Blueprint $table) {
            $table->dateTime('checked_in_at')->nullable(); // at the courts; only checked-in players get called
            $table->boolean('paid')->default(false); // from the organizer's paid list
        });

        Schema::table('open_plays', function (Blueprint $table) {
            $table->string('share_code', 16)->nullable()->unique(); // public view-mode link /live/{code}
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropUnique(['share_code']);
            $table->dropColumn('share_code');
        });
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'paid']);
        });
        DB::table('users')->whereNotNull('organizer_status')->where('role', 'member')->update(['role' => 'organizer']);
    }
};
