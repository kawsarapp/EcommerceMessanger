<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_store_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Platform type (wordpress, custom, shopify, woocommerce, etc.)
            $table->string('platform')->default('wordpress');

            // Connection credentials
            $table->string('endpoint_url');           // https://client-site.com/wp-json/neuralcart/v1
            $table->text('api_key');                  // Encrypted API key
            $table->text('api_secret')->nullable();   // Encrypted secret (for webhook signature)
            $table->string('webhook_secret')->nullable(); // For verifying inbound webhooks

            // Status & health
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_passed')->nullable();
            $table->string('last_test_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            // Extra platform-specific config (store_id, version, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_store_connections');
    }
};
