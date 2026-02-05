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
    Schema::create('order_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('sender_id')->unique(); // ফেসবুক ইউজার আইডি
    $table->unsignedBigInteger('client_id');
    $table->json('customer_info')->nullable(); // নাম, ফোন ইত্যাদি এখানে জমবে
    $table->string('status')->default('chatting'); // chatting, ordering, completed
    $table->timestamps();
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_sessions');
    }
};
