<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function show($slug, Request $request)
    {
        // ১. Slug দিয়ে Active Client খুঁজে বের করা
        $client = Client::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // ২. ক্লায়েন্টের জন্য কুয়েরি তৈরি করা
        $query = Product::where('client_id', $client->id)
            ->where('stock_status', 'in_stock');

        // ৩. যদি ক্যাটাগরি ফিল্টার থাকে
        if ($request->has('category') && $request->category != 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // ৪. প্রোডাক্ট এবং ক্যাটাগরি ডাটা আনা
        $products = $query->latest()->get();
        // শুধুমাত্র যেসব ক্যাটাগরিতে প্রোডাক্ট আছে সেগুলোই লোড করবে
        $categories = Category::whereHas('products', function ($q) use ($client) {
            $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
        })->get();

        return view('shop.index', compact('client', 'products', 'categories'));
    }
}