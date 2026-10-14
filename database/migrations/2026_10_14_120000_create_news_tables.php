<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** What's new: updates admins write (draft until published), and who has read each one. */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 60);
            $table->string('body', 280);
            $table->string('kind', 12)->default('feature'); // feature | enhancement | fix
            $table->string('audience', 10)->default('all'); // all | member | organizer
            $table->timestamp('published_at')->nullable(); // null = draft
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('news_reads', function (Blueprint $table) {
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->primary(['news_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_reads');
        Schema::dropIfExists('news');
    }
};
