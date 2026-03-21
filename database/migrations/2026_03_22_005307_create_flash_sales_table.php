<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0); // fixed amount
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->json('product_ids')->nullable();      // null = all products
            $table->json('category_ids')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->string('banner_image')->nullable();  // optional promo image
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('flash_sales');
    }
};
