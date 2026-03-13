<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop global unique constraints
            $table->dropUnique('products_slug_unique');
            $table->dropUnique('products_sku_unique');

            // Add multi-tenant composite unique constraints
            $table->unique(['client_id', 'slug'], 'products_client_slug_unique');
            $table->unique(['client_id', 'sku'], 'products_client_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_client_slug_unique');
            $table->dropUnique('products_client_sku_unique');

            $table->unique('slug', 'products_slug_unique');
            $table->unique('sku', 'products_sku_unique');
        });
    }
};
