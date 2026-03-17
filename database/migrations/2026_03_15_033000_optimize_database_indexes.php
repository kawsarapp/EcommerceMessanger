<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ===================================================
        // 1. PRODUCTS TABLE
        // ===================================================
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'idx_products_client_stock')) {
                $table->index(['client_id', 'stock_status'], 'idx_products_client_stock');
            }
            if (!$this->indexExists('products', 'idx_products_client_category')) {
                $table->index(['client_id', 'category_id'], 'idx_products_client_category');
            }
            if (!$this->indexExists('products', 'idx_products_featured')) {
                $table->index(['client_id', 'is_featured'], 'idx_products_featured');
            }
            if (!$this->indexExists('products', 'idx_products_slug_client')) {
                $table->index(['slug', 'client_id'], 'idx_products_slug_client');
            }
        });

        // ===================================================
        // 2. ORDERS TABLE
        // ===================================================
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'idx_orders_phone_client')) {
                $table->index(['customer_phone', 'client_id'], 'idx_orders_phone_client');
            }
            if (!$this->indexExists('orders', 'idx_orders_status_client')) {
                $table->index(['order_status', 'client_id'], 'idx_orders_status_client');
            }
            if (!$this->indexExists('orders', 'idx_orders_created_client')) {
                $table->index(['client_id', 'created_at'], 'idx_orders_created_client');
            }
        });

        // ===================================================
        // 3. CONVERSATIONS TABLE
        // ===================================================
        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                if (!$this->indexExists('conversations', 'idx_conv_client_sender')) {
                    $table->index(['client_id', 'sender_id'], 'idx_conv_client_sender');
                }
                if (!$this->indexExists('conversations', 'idx_conv_created')) {
                    $table->index(['created_at'], 'idx_conv_created');
                }
                if (!$this->indexExists('conversations', 'idx_conv_status')) {
                    $table->index(['status', 'client_id'], 'idx_conv_status');
                }
            });
        }

        // ===================================================
        // 4. MESSAGES TABLE
        // ===================================================
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (!$this->indexExists('messages', 'idx_messages_conv_created')) {
                    $table->index(['conversation_id', 'created_at'], 'idx_messages_conv_created');
                }
            });
        }

        // ===================================================
        // 5. REVIEWS TABLE
        // ===================================================
        Schema::table('reviews', function (Blueprint $table) {
            if (!$this->indexExists('reviews', 'idx_reviews_product_visible')) {
                $table->index(['product_id', 'is_visible'], 'idx_reviews_product_visible');
            }
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('products', 'idx_products_client_stock');
        $this->dropIndexIfExists('products', 'idx_products_client_category');
        $this->dropIndexIfExists('products', 'idx_products_featured');
        $this->dropIndexIfExists('products', 'idx_products_slug_client');
        $this->dropIndexIfExists('orders', 'idx_orders_phone_client');
        $this->dropIndexIfExists('orders', 'idx_orders_status_client');
        $this->dropIndexIfExists('orders', 'idx_orders_created_client');
        $this->dropIndexIfExists('reviews', 'idx_reviews_product_visible');
    }

    /**
     * PostgreSQL + MySQL compatible index check
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $index]
            );
        } else {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        }

        return count($result) > 0;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
        }
    }
};
