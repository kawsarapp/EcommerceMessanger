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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            
            // Branding & Basics
            $table->string('site_name')->default('NeuralCart');
            $table->string('logo_path')->nullable(); // For future logo upload
            $table->string('phone')->default('01771545972');
            $table->string('email')->default('info@asianhost.net');
            $table->string('address')->default('Dhaka, Bangladesh');
            
            // Social Links
            $table->string('facebook_link')->nullable();
            $table->string('youtube_link')->nullable();
            
            // Hero Section
            $table->string('hero_badge')->default('বাংলাদেশে এই প্রথম - Next Gen AI Sales');
            $table->string('hero_title_part1')->default('আপনার বিজনেসকে করুন');
            $table->string('hero_title_part2')->default('Automated Machine');
            $table->text('hero_subtitle')->nullable();
            
            // Pain Points (JSON array of {icon, title, desc})
            $table->json('pain_points')->nullable();
            
            // Core Features (JSON array of {icon, title, desc, color_class})
            $table->json('features')->nullable();
            
            // Cost Comparison settings
            $table->json('cost_comparison')->nullable(); // Holds custom manual vs AI text & prices
            
            // Footer
            $table->text('footer_text')->nullable();
            $table->string('developer_name')->default('Kawsar Ahmed');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
