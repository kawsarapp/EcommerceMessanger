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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('fb_pixel_id')->nullable()->after('fb_page_id');
            $table->integer('popup_delay')->default(3)->after('popup_expires_at');
            $table->json('popup_pages')->nullable()->after('popup_delay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['fb_pixel_id', 'popup_delay', 'popup_pages']);
        });
    }
};
