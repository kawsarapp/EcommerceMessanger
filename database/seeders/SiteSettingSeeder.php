<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'NeuralCart',
                'phone' => '01771545972',
                'email' => 'info@asianhost.net',
                'address' => 'Dhaka, Bangladesh',
                'facebook_link' => '#',
                'youtube_link' => '#',
                
                'hero_badge' => 'বাংলাদেশে এই প্রথম - Next Gen AI Sales',
                'hero_title_part1' => 'আপনার বিজনেসকে করুন',
                'hero_title_part2' => 'Automated Machine',
                'hero_subtitle' => '২৪/৭ কাস্টমার সাপোর্ট, অটো অর্ডার কনফার্মেশন এবং নির্ভুল ইনভেন্টরি ম্যানেজমেন্ট। মানুষ ঘুমালেও, আপনার NeuralCart AI ঘুমাবে না।',
                
                'footer_text' => 'বাংলাদেশের ক্ষুদ্র ও মাঝারি উদ্যোক্তাদের জন্য তৈরি #১ AI সেলস অ্যাসিস্ট্যান্ট। আমরা প্রযুক্তির মাধ্যমে আপনার ব্যবসাকে সহজ করি।',
                'developer_name' => 'Kawsar Ahmed',
                
                'pain_points' => [
                    [
                        'icon' => 'fas fa-clock',
                        'title' => 'স্লো রেসপন্স = সেল লস',
                        'desc' => 'আপনি যখন ঘুমাচ্ছেন বা ব্যস্ত, তখন কাস্টমার মেসেজ দিচ্ছে। ১ ঘণ্টা পর রিপ্লাই দিলে সেই কাস্টমার আর থাকে না, চলে যায় অন্য পেজে।'
                    ],
                    [
                        'icon' => 'fas fa-wallet',
                        'title' => 'অতিরিক্ত স্টাফ খরচ',
                        'desc' => '২৪ ঘণ্টা সাপোর্ট দিতে গেলে ৩ শিফটে মানুষ লাগে। বেতন, বোনাস, ইন্টারনেট খরচ মিলিয়ে আপনার প্রফিটের অর্ধেক চলে যায় স্টাফ খরচে।'
                    ],
                    [
                        'icon' => 'fas fa-exclamation-triangle',
                        'title' => 'ভুল অর্ডার ও ফ্রড',
                        'desc' => 'মানুষের ভুলে ভুল প্রোডাক্ট ডেলিভারি হয়। এছাড়া ফেইক অর্ডার আইডেন্টিফাই করতে না পারায় ডেলিভারি চার্জ লস হয়।'
                    ]
                ],
                
                'features' => [
                    [
                        'icon' => 'fas fa-comments',
                        'color_class' => 'blue',
                        'title' => 'Instant Reply (0 Sec)',
                        'desc' => 'কাস্টমার হাই দেওয়া মাত্রই রিপ্লাই। প্রোডাক্টের দাম, সাইজ, ছবি—সব অটোমেটিক সেন্ড করবে।'
                    ],
                    [
                        'icon' => 'fas fa-boxes',
                        'color_class' => 'purple',
                        'title' => 'Smart Inventory',
                        'desc' => 'স্টকে পণ্য না থাকলে অর্ডার নিবে না, বরং "Restock Alert" সেট করবে। আপনার ম্যানুয়াল চেক করার দরকার নেই।'
                    ],
                    [
                        'icon' => 'fas fa-user-shield',
                        'color_class' => 'green',
                        'title' => 'Fraud Detection',
                        'desc' => 'যারা আগে অর্ডার করে পণ্য নেয়নি, তাদের চিনে রাখবে এবং আপনাকে সতর্ক করবে। ডেলিভারি চার্জ লস হবে না।'
                    ],
                    [
                        'icon' => 'fas fa-camera',
                        'color_class' => 'orange',
                        'title' => 'Visual Search',
                        'desc' => 'কাস্টমার কোনো জামার ছবি দিলে AI সেটা দেখে আপনার স্টকের সাথে মিলিয়ে বের করে দিবে।'
                    ],
                    [
                        'icon' => 'fas fa-brain',
                        'color_class' => 'pink',
                        'title' => 'Human Psychology',
                        'desc' => 'রোবটের মতো নয়, মানুষের মতো কথা বলে। কাস্টমার দাম বেশি বললে কনভেন্স করে সেল ক্লোজ করে।'
                    ],
                    [
                        'icon' => 'fas fa-chart-line',
                        'color_class' => 'cyan',
                        'title' => 'Daily Report',
                        'desc' => 'দিন শেষে টেলিগ্রামে রিপোর্ট পাঠাবে—কত টাকা সেল হলো, কতটি অর্ডার পেন্ডিং।'
                    ]
                ],
                
                'cost_comparison' => [
                    'manual_title' => 'Manual Human Team',
                    'manual_scenario' => 'Scenario A: ১৫ জন মডারেটর (৩ শিফট)',
                    'manual_salary' => '১,৫০,০০০ ৳',
                    'manual_overhead' => '৮০,০০০ ৳',
                    'manual_loss' => '২০,০০০ ৳',
                    'manual_total' => '২,৫০,০০০ ৳',
                    'ai_title' => 'NeuralCart AI',
                    'ai_scenario' => 'Scenario B: Fully Automated (24/7)',
                    'ai_salary' => '০ ৳ (Zero)',
                    'ai_capacity' => 'UNLIMITED',
                    'ai_accuracy' => '100% / <1 Sec Reply',
                    'ai_total' => '৫,০০০ - ১০,০০০ ৳',
                ]
            ]
        );
    }
}
