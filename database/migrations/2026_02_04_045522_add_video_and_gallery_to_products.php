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
    Schema::table('products', function (Blueprint $table) {
        // যদি video_url কলামটি না থাকে তবেই যোগ করো
        if (!Schema::hasColumn('products', 'video_url')) {
            $table->string('video_url')->nullable();
        }

        // যদি gallery কলামটি না থাকে তবেই যোগ করো
        if (!Schema::hasColumn('products', 'gallery')) {
            $table->json('gallery')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
