<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            // FK to coupon (if not already exists)
            if (!Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('total_amount')->constrained()->nullOnDelete();
            }
            // Discount amount (separate from total_amount)
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_id');
            }
            // Referral tracking
            if (!Schema::hasColumn('orders', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->after('discount_amount');
            }
            // Platform where order came from (if not already)
            if (!Schema::hasColumn('orders', 'platform')) {
                $table->string('platform')->default('messenger')->after('referral_code');
            }
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropConstrainedForeignId('coupon_id');
            }
            foreach (['discount_amount', 'referral_code', 'platform'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
