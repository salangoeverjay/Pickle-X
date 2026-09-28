<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_plays', function (Blueprint $table) {
            $table->string('pay_method', 16)->nullable(); // gcash | maya | qrph | other; null = no payment QR
            $table->string('pay_name')->nullable();
            $table->string('pay_number', 40)->nullable();
            $table->string('pay_qr_version', 12)->nullable(); // cache-busting part of the QR image URL
        });

        // The organizer's QR image (re-encoded PNG, base64), kept out of the open_plays row.
        Schema::create('payment_qrs', function (Blueprint $table) {
            $table->foreignId('open_play_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('data');
            $table->timestamps();
        });

        // "I've sent the payment" from a member; the organizer still confirms.
        Schema::create('pay_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['open_play_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_claims');
        Schema::dropIfExists('payment_qrs');
        Schema::table('open_plays', function (Blueprint $table) {
            $table->dropColumn(['pay_method', 'pay_name', 'pay_number', 'pay_qr_version']);
        });
    }
};
