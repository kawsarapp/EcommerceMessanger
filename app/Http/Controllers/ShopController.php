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
        // ১. Slug দিয়ে Active Client খুঁজে বের করা
        $client = Client::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // ২. প্রোডাক্ট কুয়েরি বিল্ড (পেজিনেশন সহ)
        $query = Product::where('client_id', $client->id)
            ->where('stock_status', 'in_stock');

        // ৩. ক্যাটাগরি ফিল্টার (যদি থাকে)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // ৪. প্রোডাক্ট পেজিনেট করে লোড করা (20 per page)
        $products = $query->with('category')->latest()->paginate(20);

        // ৫. শুধুমাত্র যেসব ক্যাটাগরিতে প্রোডাক্ট আছে, সেগুলো লোড করা
        $categories = Category::whereHas('products', function ($q) use ($client) {
            $q->where('client_id', $client->id)
              ->where('stock_status', 'in_stock');
        })->get();

        // ৬. ভিউ রিটার্ন
        return view('shop.index', compact('client', 'products', 'categories'));
    }
}