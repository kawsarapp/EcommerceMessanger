<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // ১. ক্লায়েন্ট এবং কাস্টমার আইডেন্টিফিকেশন
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('sender_id')->index(); // Facebook PSID (WebhookController এর সাথে মিল রেখে)
            $table->string('platform')->default('messenger'); // messenger, whatsapp
            
            // ২. কথোপকথন (Interaction Pair)
            $table->text('user_message')->nullable(); // কাস্টমার কী লিখেছে
            $table->text('bot_response')->nullable(); // AI কী উত্তর দিয়েছে
            $table->string('attachment_url')->nullable(); // যদি কাস্টমার ছবি পাঠায়
            
            // ৩. এনালাইটিক্স ও মেটাডাটা (SaaS এর জন্য গুরুত্বপূর্ণ)
            $table->unsignedInteger('tokens_used')->default(0); // কতগুলো AI টোকেন খরচ হলো (ভবিষ্যতের জন্য)
            $table->json('metadata')->nullable(); // AI কি অর্ডার প্রসেস করেছে? নাকি প্রোডাক্ট খুঁজেছে?
            
            // ৪. স্ট্যাটাস
            $table->enum('status', ['success', 'failed', 'human_takeover'])->default('success');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};