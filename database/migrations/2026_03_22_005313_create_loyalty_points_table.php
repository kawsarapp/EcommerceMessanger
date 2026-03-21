<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('sender_id');         // customer platform id
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->integer('points')->default(0); // can be negative for redemption
            $table->enum('type', ['earned', 'redeemed', 'expired', 'bonus'])->default('earned');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'sender_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('loyalty_points');
    }
};
