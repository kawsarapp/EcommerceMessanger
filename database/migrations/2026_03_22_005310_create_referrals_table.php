<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('sender_id');         // referrer (messenger/wa id)
            $table->string('referral_code', 20)->unique();
            $table->string('referred_sender_id')->nullable(); // who used the code
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0); // new customer discount
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
            $table->timestamps();
            $table->index(['client_id', 'referral_code']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('referrals');
    }
};
