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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('client_id')->constrained()->onDelete('cascade');
		$table->string('sender_id')->nullable()->index();
        $table->string('customer_name');
        $table->string('customer_phone');
        $table->text('shipping_address');
        $table->decimal('total_amount', 10, 2);
        $table->string('payment_status')->default('pending'); // pending, paid
        $table->string('order_status')->default('processing'); // processing, shipped, delivered, cancelled
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
