<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // ─── Starter Plan ───────────────────────────────────────────
            [
                'name'                      => 'Starter',
                'description'               => 'ছোট ব্যবসার জন্য আদর্শ — AI Bot দিয়ে শুরু করুন',
                'price'                     => 1999.00,
                'yearly_price'              => 19200.00,
                'duration_days'             => 30,
                'trial_days'                => 7,
                'is_active'                 => true,
                'is_featured'               => false,
                'badge_text'                => null,
                'sort_order'                => 1,
                // Limits
                'product_limit'             => 100,
                'order_limit'               => 500,
                'ai_message_limit'          => 2000,
                'whatsapp_limit'            => 0,
                'storage_limit_mb'          => 500,
                'staff_account_limit'       => 1,
                // Core Features
                'allow_ai'                  => true,
                'allow_whatsapp'            => false,
                'allow_telegram'            => true,
                'allow_facebook_messenger'  => true,
                'allow_instagram'           => false,
                'allow_coupon'              => true,
                'allow_review'              => true,
                'allow_analytics'           => false,
                'allow_custom_domain'       => true,
                'allow_abandoned_cart'      => false,
                'allow_marketing_broadcast' => false,
                'remove_branding'           => false,
                'priority_support'          => false,
                'allow_api_access'          => false,
                'allow_own_api_key'         => false,
                // Delivery & Payments
                'allow_payment_gateway'     => true,
                'allow_delivery_integration'=> true,
                'allow_premium_themes'      => false,
                // Sales & Growth
                'allow_flash_sale'          => false,
                'allow_loyalty'             => false,
                'allow_referral'            => false,
                'allow_return_refund'       => false,
                'allow_webhook'             => false,
                'allow_api_rate_limit'      => false,
                // Premium Add-ons
                'allow_popup_banner'        => true,
                'allow_bulk_import'         => false,
                'allow_product_video'       => false,
                'allow_advanced_seo'        => false,
                'allow_multi_currency'      => false,
                'allow_pos_mode'            => false,
                'allow_custom_checkout'     => true,
                'allow_subscription_product'=> false,
                'allow_live_chat_support'   => false,
                'allow_email_marketing'     => false,
                'allow_sms_notification'    => false,
                'allow_store_locator'       => false,
                'allow_product_comparison'  => false,
                'features'                  => ['AI Chatbot','Facebook Messenger','Telegram Bot','Custom Domain','Coupon System','Customer Reviews','Courier Integration','100 Products','500 Orders/month'],
            ],
            [
                'name'                      => 'Professional',
                'description'               => 'বাড়ন্ত ব্যবসার জন্য — সব channel-এ AI চালু করুন',
                'price'                     => 3999.00,
                'yearly_price'              => 38400.00,
                'duration_days'             => 30,
                'trial_days'                => 7,
                'is_active'                 => true,
                'is_featured'               => true,
                'badge_text'                => '🔥 Most Popular',
                'sort_order'                => 2,
                // Limits
                'product_limit'             => 0, // unlimited
                'order_limit'               => 0, // unlimited
                'ai_message_limit'          => 10000,
                'whatsapp_limit'            => 5000,
                'storage_limit_mb'          => 2048,
                'staff_account_limit'       => 3,
                // Core Features
                'allow_ai'                  => true,
                'allow_whatsapp'            => true,
                'allow_telegram'            => true,
                'allow_facebook_messenger'  => true,
                'allow_instagram'           => true,
                'allow_coupon'              => true,
                'allow_review'              => true,
                'allow_analytics'           => true,
                'allow_custom_domain'       => true,
                'allow_abandoned_cart'      => true,
                'allow_marketing_broadcast' => true,
                'remove_branding'           => false,
                'priority_support'          => false,
                'allow_api_access'          => true,
                'allow_own_api_key'         => false,
                // Delivery & Payments
                'allow_payment_gateway'     => true,
                'allow_delivery_integration'=> true,
                'allow_premium_themes'      => true,
                // Sales & Growth
                'allow_flash_sale'          => true,
                'allow_loyalty'             => true,
                'allow_referral'            => true,
                'allow_return_refund'       => true,
                'allow_webhook'             => false,
                'allow_api_rate_limit'      => false,
                // Premium Add-ons
                'allow_popup_banner'        => true,
                'allow_bulk_import'         => false,
                'allow_product_video'       => true,
                'allow_advanced_seo'        => false,
                'allow_multi_currency'      => false,
                'allow_pos_mode'            => false,
                'allow_custom_checkout'     => true,
                'allow_subscription_product'=> false,
                'allow_live_chat_support'   => false,
                'allow_email_marketing'     => true,
                'allow_sms_notification'    => true,
                'allow_store_locator'       => false,
                'allow_product_comparison'  => true,
                'features'                  => ['সব Starter Features','WhatsApp & Instagram Bot','Flash Sale + Countdown','Loyalty Points Program','Referral System','Marketing Broadcast','Abandoned Cart Recovery','Advanced Analytics','Email & SMS Notifications','Return/Refund Flow','16+ Premium Themes','Unlimited Products & Orders','3 Staff Accounts'],
            ],
            [
                'name'                      => 'Enterprise',
                'description'               => 'বড় ব্যবসার জন্য — সব কিছু আনলিমিটেড, white-label সহ',
                'price'                     => 7999.00,
                'yearly_price'              => 76800.00,
                'duration_days'             => 30,
                'trial_days'                => 7,
                'is_active'                 => true,
                'is_featured'               => false,
                'badge_text'                => '👑 Best Value',
                'sort_order'                => 3,
                // Limits
                'product_limit'             => 0,
                'order_limit'               => 0,
                'ai_message_limit'          => 0, // unlimited
                'whatsapp_limit'            => 0, // unlimited
                'storage_limit_mb'          => 10240,
                'staff_account_limit'       => 10,
                // Core Features
                'allow_ai'                  => true,
                'allow_whatsapp'            => true,
                'allow_telegram'            => true,
                'allow_facebook_messenger'  => true,
                'allow_instagram'           => true,
                'allow_coupon'              => true,
                'allow_review'              => true,
                'allow_analytics'           => true,
                'allow_custom_domain'       => true,
                'allow_abandoned_cart'      => true,
                'allow_marketing_broadcast' => true,
                'remove_branding'           => true,
                'priority_support'          => true,
                'allow_api_access'          => true,
                'allow_own_api_key'         => true,
                // Delivery & Payments
                'allow_payment_gateway'     => true,
                'allow_delivery_integration'=> true,
                'allow_premium_themes'      => true,
                // Sales & Growth
                'allow_flash_sale'          => true,
                'allow_loyalty'             => true,
                'allow_referral'            => true,
                'allow_return_refund'       => true,
                'allow_webhook'             => true,
                'allow_api_rate_limit'      => true,
                // Premium Add-ons
                'allow_popup_banner'        => true,
                'allow_bulk_import'         => true,
                'allow_product_video'       => true,
                'allow_advanced_seo'        => true,
                'allow_multi_currency'      => true,
                'allow_pos_mode'            => true,
                'allow_custom_checkout'     => true,
                'allow_subscription_product'=> true,
                'allow_live_chat_support'   => true,
                'allow_email_marketing'     => true,
                'allow_sms_notification'    => true,
                'allow_store_locator'       => true,
                'allow_product_comparison'  => true,
                'features'                  => ['সব Professional Features','নিজের AI API Key','Zapier/Make Webhook','POS Mode','Bulk CSV Import','Advanced SEO Tools','Multi-Currency Display','Subscription Products','Store Locator Map','White-label (No Branding)','Priority Support 24/7','10 Staff Accounts','Unlimited Storage','API Rate Limiting'],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        $this->command->info('✅ 3 Plans seeded: Starter (১,৯৯৯৳), Professional (৩,৯৯৯৳), Enterprise (৭,৯৯৯৳)');
    }
}
