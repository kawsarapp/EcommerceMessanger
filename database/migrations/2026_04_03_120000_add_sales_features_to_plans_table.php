<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales & Growth features + নতুন premium features plans table-এ যোগ।
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {

            // ─── Sales & Growth ─────────────────────────────────────
            if (!Schema::hasColumn('plans', 'allow_flash_sale')) {
                $table->boolean('allow_flash_sale')->default(false)->comment('Flash sale countdown তৈরি করতে পারবে');
            }
            if (!Schema::hasColumn('plans', 'allow_loyalty')) {
                $table->boolean('allow_loyalty')->default(false)->comment('Customer loyalty points system');
            }
            if (!Schema::hasColumn('plans', 'allow_referral')) {
                $table->boolean('allow_referral')->default(false)->comment('Referral/affiliate program');
            }
            if (!Schema::hasColumn('plans', 'allow_return_refund')) {
                $table->boolean('allow_return_refund')->default(false)->comment('Return/Refund request management');
            }
            if (!Schema::hasColumn('plans', 'allow_webhook')) {
                $table->boolean('allow_webhook')->default(false)->comment('Zapier/Make.com webhook integration');
            }
            if (!Schema::hasColumn('plans', 'allow_api_rate_limit')) {
                $table->boolean('allow_api_rate_limit')->default(false)->comment('API rate limiting per client');
            }

            // ─── New Premium Features ────────────────────────────────
            if (!Schema::hasColumn('plans', 'allow_instagram')) {
                $table->boolean('allow_instagram')->default(false)->comment('Instagram DM bot & auto-reply');
            }
            if (!Schema::hasColumn('plans', 'allow_sms_notification')) {
                $table->boolean('allow_sms_notification')->default(false)->comment('SMS order notification to customer');
            }
            if (!Schema::hasColumn('plans', 'allow_popup_banner')) {
                $table->boolean('allow_popup_banner')->default(true)->comment('Popup offer banner on storefront');
            }
            if (!Schema::hasColumn('plans', 'allow_multi_currency')) {
                $table->boolean('allow_multi_currency')->default(false)->comment('Multi-currency price display');
            }
            if (!Schema::hasColumn('plans', 'allow_product_video')) {
                $table->boolean('allow_product_video')->default(false)->comment('YouTube/Video embed in product page');
            }
            if (!Schema::hasColumn('plans', 'allow_bulk_import')) {
                $table->boolean('allow_bulk_import')->default(false)->comment('CSV/Excel থেকে bulk product import');
            }
            if (!Schema::hasColumn('plans', 'allow_custom_checkout')) {
                $table->boolean('allow_custom_checkout')->default(true)->comment('Custom checkout fields & form builder');
            }
            if (!Schema::hasColumn('plans', 'allow_pos_mode')) {
                $table->boolean('allow_pos_mode')->default(false)->comment('Point of Sale (POS) offline mode');
            }
            if (!Schema::hasColumn('plans', 'allow_live_chat_support')) {
                $table->boolean('allow_live_chat_support')->default(false)->comment('Live chat support within dashboard');
            }
            if (!Schema::hasColumn('plans', 'allow_email_marketing')) {
                $table->boolean('allow_email_marketing')->default(false)->comment('Email newsletter & campaign blasts');
            }
            if (!Schema::hasColumn('plans', 'allow_advanced_seo')) {
                $table->boolean('allow_advanced_seo')->default(false)->comment('Schema markup, sitemap, Open Graph');
            }
            if (!Schema::hasColumn('plans', 'allow_subscription_product')) {
                $table->boolean('allow_subscription_product')->default(false)->comment('Recurring subscription products');
            }
            if (!Schema::hasColumn('plans', 'allow_store_locator')) {
                $table->boolean('allow_store_locator')->default(false)->comment('Physical store location map widget');
            }
            if (!Schema::hasColumn('plans', 'allow_product_comparison')) {
                $table->boolean('allow_product_comparison')->default(false)->comment('Side-by-side product comparison tool');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $cols = [
                'allow_flash_sale', 'allow_loyalty', 'allow_referral',
                'allow_return_refund', 'allow_webhook', 'allow_api_rate_limit',
                'allow_instagram', 'allow_sms_notification', 'allow_popup_banner',
                'allow_multi_currency', 'allow_product_video', 'allow_bulk_import',
                'allow_custom_checkout', 'allow_pos_mode', 'allow_live_chat_support',
                'allow_email_marketing', 'allow_advanced_seo', 'allow_subscription_product',
                'allow_store_locator', 'allow_product_comparison',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
