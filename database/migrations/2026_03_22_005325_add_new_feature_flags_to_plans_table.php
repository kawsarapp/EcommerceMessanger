<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_flash_sale')->default(false)->after('allow_analytics');
            $table->boolean('allow_referral')->default(false)->after('allow_flash_sale');
            $table->boolean('allow_loyalty')->default(false)->after('allow_referral');
            $table->boolean('allow_return_refund')->default(false)->after('allow_loyalty');
            $table->boolean('allow_webhook')->default(false)->after('allow_return_refund');
            $table->integer('api_rate_limit')->default(60)->after('allow_webhook'); // requests/min
        });
    }
    public function down(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'allow_flash_sale','allow_referral','allow_loyalty',
                'allow_return_refund','allow_webhook','api_rate_limit'
            ]);
        });
    }
};
