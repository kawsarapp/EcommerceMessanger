<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // is_global = true  → Super admin created, all sellers can use it
            // is_global = false → Seller created, only that seller can see it
            $table->boolean('is_global')->default(false);
        });

        // Mark all categories with client_id = NULL as global (if any)
        DB::table('categories')->whereNull('client_id')->update(['is_global' => true]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
