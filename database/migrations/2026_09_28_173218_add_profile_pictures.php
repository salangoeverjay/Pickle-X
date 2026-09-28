<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // "preset:dink" (a PickleX avatar) or "photo:{hash}" (uploaded). Null = initials on avatar_color.
            $table->string('avatar', 64)->nullable();
        });

        // Uploaded photos live in the DB (256px JPEG, base64) so they survive serverless hosts with no disk.
        Schema::create('avatar_photos', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_photos');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
