<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('email'); // member | organizer | admin
            $table->string('organizer_status')->nullable()->after('role'); // pending | approved | declined
            $table->string('club')->nullable();
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('courts')->nullable();
            $table->text('organizer_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'organizer_status', 'club', 'city', 'courts', 'organizer_note']);
        });
    }
};
