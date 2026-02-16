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
        // যদি এই কলামগুলো আগে না থাকে, তবেই অ্যাড হবে
        if (!Schema::hasColumn('clients', 'primary_color')) {
            $table->string('primary_color')->default('#4f46e5')->nullable();
        }
        if (!Schema::hasColumn('clients', 'announcement_text')) {
            $table->text('announcement_text')->nullable();
        }
        if (!Schema::hasColumn('clients', 'pixel_id')) {
            $table->string('pixel_id')->nullable();
        }
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
            //
        });
    }
};
