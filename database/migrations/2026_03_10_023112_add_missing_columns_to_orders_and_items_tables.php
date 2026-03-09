<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ১. Orders টেবিলে কাস্টমার নোট কলাম যুক্ত করা
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'customer_note')) {
                $table->text('customer_note')->nullable()->after('order_status');
            }
        });

        // ২. Order Items টেবিলে Unit Price এবং Attributes কলাম যুক্ত করা
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('order_items', 'attributes')) {
                $table->json('attributes')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_note');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'attributes']);
        });
    }
};