<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Category: প্রতিটি ক্যাটাগরি থেকে হোমপেজে কয়টি প্রোডাক্ট দেখাবে
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('homepage_products_count')->default(4)->after('sort_order');
        });

        // Client: হোমপেজে ক্যাটাগরি সেকশনের উপরে একটি গ্লোবাল অফার ব্যানার
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('homepage_banner_active')->default(false);
            $table->string('homepage_banner_image')->nullable();
            $table->string('homepage_banner_title')->nullable();
            $table->text('homepage_banner_subtitle')->nullable();
            $table->string('homepage_banner_link')->nullable();
            $table->dateTime('homepage_banner_timer')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('homepage_products_count');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'homepage_banner_active',
                'homepage_banner_image',
                'homepage_banner_title',
                'homepage_banner_subtitle',
                'homepage_banner_link',
                'homepage_banner_timer',
            ]);
        });
    }
};
