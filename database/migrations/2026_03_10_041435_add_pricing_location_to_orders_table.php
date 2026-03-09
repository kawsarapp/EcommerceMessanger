<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Location Fields
            if (!Schema::hasColumn('orders', 'customer_email')) $table->string('customer_email')->nullable()->after('customer_phone');
            if (!Schema::hasColumn('orders', 'division')) $table->string('division')->nullable()->after('shipping_address');
            if (!Schema::hasColumn('orders', 'district')) $table->string('district')->nullable()->after('division');
            
            // Pricing Fields
            if (!Schema::hasColumn('orders', 'subtotal')) $table->decimal('subtotal', 10, 2)->default(0)->after('district');
            if (!Schema::hasColumn('orders', 'shipping_charge')) $table->decimal('shipping_charge', 10, 2)->default(0)->after('subtotal');
            if (!Schema::hasColumn('orders', 'discount_amount')) $table->decimal('discount_amount', 10, 2)->default(0)->after('shipping_charge');
            if (!Schema::hasColumn('orders', 'coupon_code')) $table->string('coupon_code')->nullable()->after('discount_amount');
            
            // Order Status Fields
            if (!Schema::hasColumn('orders', 'payment_method')) $table->string('payment_method')->default('cod')->after('total_amount');
            if (!Schema::hasColumn('orders', 'is_guest_checkout')) $table->boolean('is_guest_checkout')->default(true)->after('order_status');
        });
    }

    public function down(): void
    {
        // Safety purpose: we don't drop columns automatically in down method
    }
};