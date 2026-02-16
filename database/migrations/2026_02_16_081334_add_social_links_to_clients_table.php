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
            // চেক করা হচ্ছে কলামগুলো আগে থেকেই আছে কি না
            if (!Schema::hasColumn('clients', 'social_facebook')) {
                $table->string('social_facebook')->nullable();
            }
            if (!Schema::hasColumn('clients', 'social_instagram')) {
                $table->string('social_instagram')->nullable();
            }
            if (!Schema::hasColumn('clients', 'social_youtube')) {
                $table->string('social_youtube')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['social_facebook', 'social_instagram', 'social_youtube']);
        });
    }
};