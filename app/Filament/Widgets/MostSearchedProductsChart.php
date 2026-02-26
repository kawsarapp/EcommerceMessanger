<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Conversation;
use App\Models\Product;

class MostSearchedProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Most Searched Products (AI Logs)';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $user = auth()->user();
        $clientId = $user->client->id ?? null;

        // সেলারের অ্যাক্টিভ প্রোডাক্টগুলো লোড করা
        $productQuery = Product::query();
        if ($user->id !== 1 && $clientId) {
            $productQuery->where('client_id', $clientId);
        }
        $products = $productQuery->latest()->limit(15)->get();
        
        $labels = [];
        $counts = [];

        foreach ($products as $product) {
            // Conversation টেবিলে কাস্টমারের মেসেজে এই প্রোডাক্টের নাম কতবার এসেছে তা কাউন্ট করা
            $mentionQuery = Conversation::where('user_message', 'LIKE', '%' . $product->name . '%');
            if ($user->id !== 1 && $clientId) {
                $mentionQuery->where('client_id', $clientId);
            }
            
            $mentionCount = $mentionQuery->count();
                
            if ($mentionCount > 0) {
                $labels[] = str()->limit($product->name, 15); // নাম বড় হলে কেটে ছোট করা
                $counts[] = $mentionCount;
            }
        }

        // ডাটাবেস ফাঁকা থাকলে ডিফল্ট মেসেজ
        if(empty($labels)) {
            $labels = ['No Search Data'];
            $counts = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Search Frequency',
                    'data' => $counts,
                    'backgroundColor' => '#3b82f6', // ব্লু কালার
                    'borderRadius' => 5, // বারের কর্নার গোল করার জন্য
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // এটি বার চার্ট হিসেবে দেখাবে
    }
}