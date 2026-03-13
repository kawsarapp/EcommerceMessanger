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
        // 1. Optimize Orders Table
        Schema::table('orders', function (Blueprint $table) {
            // Adding indexes to frequently filtered and sorted columns
            $table->index('order_status', 'idx_orders_status');
            $table->index('payment_status', 'idx_orders_payment');
            $table->index('customer_phone', 'idx_orders_phone');
            $table->index('created_at', 'idx_orders_created_at');
        });

        // 2. Optimize Conversations Table (AI Chat History)
        Schema::table('conversations', function (Blueprint $table) {
            // Speeds up pulling historical chats by time and filtering by platform
            $table->index('created_at', 'idx_conv_created_at');
            $table->index('platform', 'idx_conv_platform');
        });

        // 3. Optimize Products Table (Storefront Loading)
        Schema::table('products', function (Blueprint $table) {
            // Speeds up 'where featured=1' and 'where in_stock' queries on storefronts
            $table->index('is_featured', 'idx_products_featured');
            $table->index('stock_status', 'idx_products_stock');
            $table->index('created_at', 'idx_products_created_at');
        });
        
        // 4. Optimize Active Order Sessions (Live Cart & AI State)
        Schema::table('order_sessions', function (Blueprint $table) {
            // Speeds up identifying which sessions are abandoned vs active
            $table->index('status', 'idx_sessions_status');
            $table->index('updated_at', 'idx_sessions_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_payment');
            $table->dropIndex('idx_orders_phone');
            $table->dropIndex('idx_orders_created_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conv_created_at');
            $table->dropIndex('idx_conv_platform');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_featured');
            $table->dropIndex('idx_products_stock');
            $table->dropIndex('idx_products_created_at');
        });
        
        Schema::table('order_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_status');
            $table->dropIndex('idx_sessions_updated_at');
        });
    }
};
