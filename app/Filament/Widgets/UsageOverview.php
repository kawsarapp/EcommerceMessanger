<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\Conversation;
use Illuminate\Support\Carbon;

class UsageOverview extends BaseWidget
{
    // ড্যাশবোর্ড অটোমেটিক রিফ্রেশ হবে প্রতি ১৫ সেকেন্ডে (Real-time update)
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $userId = auth()->id();

        // ১. এডমিন (ID 1) হলে তাকে সিস্টেমের জেনারেল স্ট্যাটাস দেখাবে
        if ($userId === 1) {
            return [
                Stat::make('System Admin', 'Super Access')
                    ->description('Full system control enabled')
                    ->descriptionIcon('heroicon-m-shield-check')
                    ->color('success')
                    ->chart([7, 2, 10, 3, 15, 4, 17]),
            ];
        }

        // ২. লগইন করা ইউজারের ক্লায়েন্ট ডাটা এবং প্ল্যান আনা (N+1 Solved using 'with')
        $client = Client::with('plan')->where('user_id', $userId)->first();

        // ৩. যদি ক্লায়েন্ট না থাকে অথবা প্ল্যান এসাইন করা না থাকে (Pending Approval Logic)
        if (!$client || !$client->plan) {
            return [
                Stat::make('Account Status', 'Pending Approval')
                    ->description('Please contact Admin to activate your plan.')
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color('danger')
                    ->extraAttributes([
                        'class' => 'ring-2 ring-red-500 bg-red-50',
                    ]),
                
                Stat::make('Access', 'Restricted')
                    ->description('You cannot create products or receive orders yet.')
                    ->color('gray')
                    ->icon('heroicon-m-no-symbol'),
            ];
        }

        // ৪. লিমিট ও পারসেন্টেজ ক্যালকুলেশন
        $plan = $client->plan;
        $now = Carbon::now();

        // --- Product Usage ---
        // N+1 Avoided: using count directly on query builder
        $productCount = Product::where('client_id', $client->id)->count();
        $productLimit = $plan->product_limit;
        $productUsage = $productLimit > 0 ? round(($productCount / $productLimit) * 100) : 0;

        // --- Monthly Order Usage ---
        $orderCount = Order::where('client_id', $client->id)
                            ->whereMonth('created_at', $now->month)
                            ->whereYear('created_at', $now->year)
                            ->count();
        $orderLimit = $plan->order_limit;
        $orderUsage = $orderLimit > 0 ? round(($orderCount / $orderLimit) * 100) : 0;

        // --- Monthly AI Message Usage ---
        $aiMsgCount = Conversation::where('client_id', $client->id)
                                    ->whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)
                                    ->count();
        $aiLimit = $plan->ai_message_limit;
        $aiUsage = $aiLimit > 0 ? round(($aiMsgCount / $aiLimit) * 100) : 0;

        // ৫. স্ট্যাটাস রিটার্ন
        return [
            Stat::make('Current Plan', $plan->name)
                ->description('Expires: ' . ($client->plan_ends_at ? Carbon::parse($client->plan_ends_at)->format('d M, Y') : 'Lifetime'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->icon('heroicon-m-credit-card'),

            Stat::make('AI Messaging Limit', $aiUsage . '% Used')
                ->description($aiMsgCount . ' / ' . $aiLimit . ' messages this month')
                ->descriptionIcon($aiUsage > 90 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-chat-bubble-left-right')
                ->chart([$aiUsage, 100 - $aiUsage])
                ->color($aiUsage > 90 ? 'danger' : ($aiUsage > 70 ? 'warning' : 'success')),

            Stat::make('Product Inventory', $productUsage . '% Used')
                ->description($productCount . ' / ' . $productLimit . ' products added')
                ->descriptionIcon('heroicon-m-cube')
                ->color($productUsage >= 100 ? 'danger' : 'info'),

            Stat::make('Monthly Orders', $orderUsage . '% Used')
                ->description($orderCount . ' / ' . $orderLimit . ' orders received')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color($orderUsage > 80 ? 'warning' : 'success'),
        ];
    }
}