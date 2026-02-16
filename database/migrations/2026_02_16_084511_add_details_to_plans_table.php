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
    Schema::table('plans', function (Blueprint $table) {
        $table->string('color')->nullable()->default('#4f46e5'); // প্ল্যানের কালার
        $table->text('description')->nullable(); // ছোট বিবরণ
        $table->boolean('is_featured')->default(false); // পপুলার প্ল্যান মার্ক করার জন্য
        $table->boolean('is_active')->default(true); // প্ল্যান চালু/বন্ধ রাখার জন্য
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            //
        });
    }
};
