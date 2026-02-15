<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name');
            $table->string('slug')->unique();
            
            // Meta Settings
            $table->string('fb_page_id')->nullable();
            $table->text('fb_page_token')->nullable();
            $table->string('fb_verify_token')->nullable();
            
            $table->string('wa_phone_number_id')->nullable();
            $table->string('wa_business_account_id')->nullable();
            $table->text('wa_access_token')->nullable();
            
            // Bot Config
            $table->boolean('is_ai_enabled')->default(true);
            $table->string('ai_model')->default('gemini-pro');
            $table->text('bot_persona')->nullable();
            
            // 🔥 NEW: Custom Prompt for Salesman Persona
            $table->text('custom_prompt')->nullable()->after('shop_name'); 
            $table->text('knowledge_base')->nullable()->after('custom_prompt');

            // 🔥 NEW: Telegram Config (Previous Step)
            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
            
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};