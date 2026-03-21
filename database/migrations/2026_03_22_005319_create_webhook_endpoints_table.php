<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');                      // "My Zapier Hook"
            $table->string('url');                       // webhook URL
            $table->string('secret')->nullable();        // HMAC secret
            $table->json('events');                      // ['order.created', 'cart.abandoned']
            $table->boolean('is_active')->default(true);
            $table->integer('retry_count')->default(3);
            $table->timestamp('last_triggered_at')->nullable();
            $table->enum('last_status', ['success', 'failed', 'pending'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('webhook_endpoints');
    }
};
