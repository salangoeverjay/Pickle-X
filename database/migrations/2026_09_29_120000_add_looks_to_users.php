<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('frame', 24)->nullable(); // avatar border (App\Support\Looks::FRAMES)
            $table->string('queue_bg', 24)->nullable(); // queue background (App\Support\Looks::QUEUE_BGS)
            $table->boolean('is_developer')->default(false); // set by an admin; unlocks the exclusive Developer look
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['frame', 'queue_bg', 'is_developer']);
        });
    }
};
