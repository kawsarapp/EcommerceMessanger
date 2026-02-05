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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Laravel Auth User
        $table->string('shop_name');
        $table->string('slug')->unique(); // shop-name
        
        // মেটা (Facebook/WhatsApp) সেটিংস
        $table->string('fb_page_id')->nullable();
        $table->text('fb_page_token')->nullable(); // Page Access Token
        $table->string('fb_verify_token')->nullable(); // Webhook ভেরিফিকেশনের জন্য
        
        $table->string('wa_phone_number_id')->nullable();
        $table->string('wa_business_account_id')->nullable();
        $table->text('wa_access_token')->nullable();
        
        // বট কনফিগারেশন
        $table->boolean('is_ai_enabled')->default(true);
        $table->string('ai_model')->default('gemini-pro'); // gemini or gpt-4
        $table->text('bot_persona')->nullable(); // বটের কথা বলার স্টাইল/নির্দেশনা
        
        $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
