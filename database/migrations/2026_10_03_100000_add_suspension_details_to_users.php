<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Why and how long (User::SUSPENSION_REASONS). No end date: until an admin restores it.
            $table->string('suspension_reason', 20)->nullable()->after('suspended_at');
            $table->string('suspension_note', 300)->nullable()->after('suspension_reason');
            $table->timestamp('suspended_until')->nullable()->after('suspension_note');
            $table->foreignId('suspended_by')->nullable()->after('suspended_until')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn(['suspension_reason', 'suspension_note', 'suspended_until']);
        });
    }
};
