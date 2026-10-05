<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Share links (/j/{code}): one per open play, resettable, with guests on or off. Guests who join through it may leave an email. */
    public function up(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->string('link_code', 12)->nullable()->unique()->after('share_code');
            $table->boolean('link_guests')->default(true)->after('link_code');
        });
        Schema::table('participants', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->boolean('via_link')->default(false)->after('from_list');
        });
    }

    public function down(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropUnique(['link_code']);
            $table->dropColumn(['link_code', 'link_guests']);
        });
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['email', 'via_link']);
        });
    }
};
