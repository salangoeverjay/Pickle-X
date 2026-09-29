<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->boolean('from_list')->default(false); // added from the organizer's paid list, not joined in the app
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('from_list');
        });
    }
};
