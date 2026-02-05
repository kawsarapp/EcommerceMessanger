<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * ডাটা সেভ হওয়ার আগে এই ফাংশনটি কল হয়।
     * এখানে আমরা অটোমেটিক client_id সেট করে দিচ্ছি।
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ১. যদি ইউজার এডমিন হয় এবং ফর্ম থেকে কোনো client_id সিলেক্ট না করে থাকে (ফিউচারের জন্য)
        // অথবা সাধারণ ইউজার হয়, তাহলে তার নিজের client_id বসে যাবে।
        
        if (!isset($data['client_id'])) {
            $data['client_id'] = auth()->user()->client?->id;
        }

        // ২. যদি কোনো কারণে client_id না পাওয়া যায় (যেমন এডমিনের নিজের কোনো শপ নেই)
        if (empty($data['client_id'])) {
            // অপশনাল: এরর শো করা বা ডিফল্ট কিছু করা
            // এখানে আপনি চাইলে ১ নম্বর আইডিতে এসাইন করতে পারেন বা এরর দিতে পারেন
            // $data['client_id'] = 1; 
        }

        return $data;
    }
    
    // প্রোডাক্ট ক্রিয়েট হওয়ার পর কোথায় যাবে (অপশনাল)
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}