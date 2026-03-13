<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * নতুন features যোগ করা হচ্ছে plans table-এ।
     * এই migration টি plans table-কে একটি পূর্ণাঙ্গ SAAS plan ম্যানেজমেন্ট সিস্টেমে রূপান্তরিত করবে।
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {

            // ─── Duration ───────────────────────────────────────────
            if (!Schema::hasColumn('plans', 'duration_days')) {
                $table->integer('duration_days')->default(30)->after('price')
                    ->comment('প্ল্যান কতদিন চলবে (30 = মাসিক)');
            }

            if (!Schema::hasColumn('plans', 'trial_days')) {
                $table->integer('trial_days')->default(0)->after('duration_days')
                    ->comment('ফ্রি ট্রায়াল কতদিন (0 = কোনো ট্রায়াল নেই)');
            }

            // ─── Extra Limits ────────────────────────────────────────
            if (!Schema::hasColumn('plans', 'whatsapp_limit')) {
                $table->integer('whatsapp_limit')->default(0)->after('ai_message_limit')
                    ->comment('WhatsApp message limit (0 = unlimited)');
            }

            if (!Schema::hasColumn('plans', 'storage_limit_mb')) {
                $table->integer('storage_limit_mb')->default(500)->after('whatsapp_limit')
                    ->comment('ফাইল স্টোরেজ লিমিট (MB)');
            }

            if (!Schema::hasColumn('plans', 'staff_account_limit')) {
                $table->integer('staff_account_limit')->default(1)->after('storage_limit_mb')
                    ->comment('কতজন staff account তৈরি করতে পারবে');
            }

            // ─── Feature Toggles ────────────────────────────────────
            if (!Schema::hasColumn('plans', 'allow_custom_domain')) {
                $table->boolean('allow_custom_domain')->default(false)->after('storage_limit_mb')
                    ->comment('কাস্টম ডোমেইন সংযোগ করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'remove_branding')) {
                $table->boolean('remove_branding')->default(false)->after('allow_custom_domain')
                    ->comment('NeuralCart watermark সরাতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'priority_support')) {
                $table->boolean('priority_support')->default(false)->after('remove_branding')
                    ->comment('দ্রুত সাপোর্ট পাবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_api_access')) {
                $table->boolean('allow_api_access')->default(false)->after('priority_support')
                    ->comment('API access পাবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_whatsapp')) {
                $table->boolean('allow_whatsapp')->default(false)->after('allow_api_access')
                    ->comment('WhatsApp bot চালু করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_telegram')) {
                $table->boolean('allow_telegram')->default(true)->after('allow_whatsapp')
                    ->comment('Telegram bot চালু করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_coupon')) {
                $table->boolean('allow_coupon')->default(true)->after('allow_telegram')
                    ->comment('Coupon/Discount system ব্যবহার করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_review')) {
                $table->boolean('allow_review')->default(true)->after('allow_coupon')
                    ->comment('Customer review system চালু থাকবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_abandoned_cart')) {
                $table->boolean('allow_abandoned_cart')->default(false)->after('allow_review')
                    ->comment('Abandoned cart recovery চালু করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_marketing_broadcast')) {
                $table->boolean('allow_marketing_broadcast')->default(false)->after('allow_abandoned_cart')
                    ->comment('Bulk marketing broadcast করতে পারবে কিনা');
            }

            if (!Schema::hasColumn('plans', 'allow_analytics')) {
                $table->boolean('allow_analytics')->default(false)->after('allow_marketing_broadcast')
                    ->comment('Advanced analytics dashboard দেখতে পারবে কিনা');
            }

            // ─── Display / Pricing Page ─────────────────────────────
            if (!Schema::hasColumn('plans', 'badge_text')) {
                $table->string('badge_text')->nullable()->after('is_featured')
                    ->comment('e.g. "Best Value", "Most Popular"');
            }

            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('badge_text')
                    ->comment('Pricing পেজে সাজানোর ক্রম');
            }

            if (!Schema::hasColumn('plans', 'yearly_price')) {
                $table->decimal('yearly_price', 10, 2)->nullable()->after('price')
                    ->comment('বার্ষিক মূল্য (ডিসকাউন্ট সহ)');
            }

            // ─── Custom Feature Bullets ─────────────────────────────
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('description')
                    ->comment('কাস্টম feature list (JSON array)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columns = [
                'duration_days', 'trial_days', 'whatsapp_limit', 'storage_limit_mb',
                'staff_account_limit', 'allow_custom_domain', 'remove_branding',
                'priority_support', 'allow_api_access', 'allow_whatsapp', 'allow_telegram',
                'allow_coupon', 'allow_review', 'allow_abandoned_cart',
                'allow_marketing_broadcast', 'allow_analytics', 'badge_text',
                'sort_order', 'yearly_price', 'features',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
