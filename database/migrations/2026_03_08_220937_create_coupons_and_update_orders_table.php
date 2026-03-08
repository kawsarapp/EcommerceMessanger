<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ১. কুপন টেবিল তৈরি
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->enum('type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('min_spend', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ২. অর্ডার টেবিলে নতুন কলাম যুক্ত করা
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->after('shipping_address')->default(0);
            $table->decimal('shipping_charge', 10, 2)->after('subtotal')->default(0);
            $table->decimal('discount_amount', 10, 2)->after('shipping_charge')->default(0);
            $table->string('coupon_code')->nullable()->after('discount_amount');
            $table->boolean('is_guest_checkout')->default(true)->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'shipping_charge', 'discount_amount', 'coupon_code', 'is_guest_checkout']);
        });
    }
};