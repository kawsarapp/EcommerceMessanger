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
            // প্রতিটি দোকানের নিজস্ব এআই ইনস্ট্রাকশন রাখার জন্য
            if (!Schema::hasColumn('clients', 'custom_prompt')) {
                $table->text('custom_prompt')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'custom_prompt')) {
                $table->dropColumn('custom_prompt');
            }
        });
    }
};