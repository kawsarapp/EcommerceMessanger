<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('ig_account_id')->nullable()->after('fb_page_id');
            $table->boolean('is_instagram_active')->default(false)->after('is_ai_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['ig_account_id', 'is_instagram_active']);
        });
    }
};