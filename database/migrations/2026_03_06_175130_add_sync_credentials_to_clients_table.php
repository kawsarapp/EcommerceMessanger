<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // WooCommerce Credentials
            $table->string('wc_store_url')->nullable();
            $table->string('wc_consumer_key')->nullable();
            $table->string('wc_consumer_secret')->nullable();
            
            // Shopify Credentials (ভবিষ্যতের জন্য)
            $table->string('shopify_store_url')->nullable();
            $table->string('shopify_access_token')->nullable();
            
            // Sync Tracking
            $table->timestamp('last_inventory_sync_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'wc_store_url', 'wc_consumer_key', 'wc_consumer_secret',
                'shopify_store_url', 'shopify_access_token', 'last_inventory_sync_at'
            ]);
        });
    }
};