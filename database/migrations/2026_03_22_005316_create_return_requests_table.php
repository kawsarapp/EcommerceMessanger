<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('sender_id');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('reason');
            $table->enum('reason_type', ['defective', 'wrong_item', 'size_issue', 'not_as_described', 'other'])->default('other');
            $table->enum('status', ['requested', 'approved', 'rejected', 'returned', 'refunded'])->default('requested');
            $table->text('admin_note')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('image_url')->nullable();   // customer can send photo
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('return_requests');
    }
};
