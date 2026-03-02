<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('social_comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('client_id')->constrained()->cascadeOnDelete();
        $table->string('platform')->default('facebook'); // facebook, instagram
        $table->string('comment_id')->unique();
        $table->string('sender_id')->nullable();
        $table->string('sender_name')->nullable();
        $table->text('comment_text');
        $table->text('reply_text')->nullable(); // AI বা ম্যানুয়াল রিপ্লাই টেক্সট
        $table->enum('status', ['pending', 'auto_replied', 'manual_replied', 'ignored'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_comments');
    }
};
