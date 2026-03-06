<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('client_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
        $table->string('sender_id');
        $table->string('customer_name')->nullable();
        $table->integer('rating')->default(5);
        $table->text('comment')->nullable();
        $table->boolean('is_visible')->default(true); // প্রোডাক্ট পেজে দেখানোর কন্ট্রোল
        $table->timestamps();
    });
    
    // Order টেবিলে একটি ট্র্যাকিং কলাম লাগবে যাতে বারবার মেসেজ না যায়
    Schema::table('orders', function (Blueprint $table) {
        $table->boolean('is_review_requested')->default(false);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
