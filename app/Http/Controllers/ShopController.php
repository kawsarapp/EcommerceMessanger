<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * দোকানের হোমপেজ (প্রোডাক্ট লিস্ট)
     */
    public function show($slug, Request $request)
    {
        // ১. Slug দিয়ে Active Client খুঁজে বের করা
        $client = Client::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // ২. বেসিক প্রোডাক্ট কুয়েরি (স্টক সহ)
        $query = Product::where('client_id', $client->id)
            ->where('stock_status', 'in_stock');

        // 🔥 ফিচার ১: সার্চ অপশন (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // 🔥 ফিচার ২: ক্যাটাগরি ফিল্টার (Category Filter)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 🔥 ফিচার ৩: প্রাইস রেঞ্জ ফিল্টার (Price Range)
        if ($request->filled('min_price')) {
            $query->where('regular_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('regular_price', '<=', $request->max_price);
        }

        // 🔥 ফিচার ৪: সর্টিং (Sorting)
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('sale_price', 'asc')->orderBy('regular_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('sale_price', 'desc')->orderBy('regular_price', 'desc');
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                default: // newest
                    $query->latest();
                    break;
            }
        } else {
            $query->latest(); // ডিফল্ট সর্টিং
        }

        // ৩. প্রোডাক্ট পেজিনেট করে লোড করা (Eager Loading সহ)
        $products = $query->with('category')->paginate(12)->withQueryString();

        // ৪. সাইডবারের জন্য ক্যাটাগরি লোড করা (শুধুমাত্র যেসব ক্যাটাগরিতে প্রোডাক্ট আছে)
        $categories = Category::whereHas('products', function ($q) use ($client) {
            $q->where('client_id', $client->id)
              ->where('stock_status', 'in_stock');
        })->get();

        // ৫. ভিউ রিটার্ন
        return view('shop.index', compact('client', 'products', 'categories'));
    }

    /**
     * 🔥 ফিচার ৫: সিঙ্গেল প্রোডাক্ট ডিটেইলস পেজ
     */
    public function productDetails($slug, $productSlug)
    {
        // ১. ক্লায়েন্ট চেক
        $client = Client::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // ২. নির্দিষ্ট প্রোডাক্ট খুঁজে বের করা
        $product = Product::where('client_id', $client->id)
            ->where('slug', $productSlug)
            ->with(['category']) // ইমেজ গ্যালারি থাকলে এখানে যোগ করবেন, যেমন: ->with(['category', 'images'])
            ->firstOrFail();

        // ৩. রিলেটেড প্রোডাক্ট (Related Products) - একই ক্যাটাগরির অন্য ৪টি প্রোডাক্ট
        $relatedProducts = Product::where('client_id', $client->id)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id) // বর্তমান প্রোডাক্ট বাদ দিয়ে
            ->where('stock_status', 'in_stock')
            ->inRandomOrder()
            ->take(4)
            ->get();

        // ৪. ভিউ রিটার্ন (নতুন ব্লেড ফাইল: shop/product.blade.php)
        return view('shop.product', compact('client', 'product', 'relatedProducts'));
    }


    /**
     * অর্ডার ট্র্যাকিং ফর্ম পেজ
     */
    public function trackOrder($slug)
    {
        $client = Client::where('slug', $slug)->where('status', 'active')->firstOrFail();
        return view('shop.tracking', compact('client'));
    }

    /**
     * অর্ডার খোঁজার লজিক
     */
    public function trackOrderSubmit(Request $request, $slug)
    {
        $request->validate([
            'phone' => 'required|min:11',
        ]);

        $client = Client::where('slug', $slug)->firstOrFail();
        
        // বাংলা নম্বরকে ইংরেজিতে কনভার্ট (যেমন: ০১৭১... -> 0171...)
        $phone = $request->phone;
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $phone = str_replace($bn, $en, $phone);

        // শেষ ৫টি অর্ডার বের করা
        $orders = \App\Models\Order::where('client_id', $client->id)
            ->where('customer_phone', 'LIKE', "%{$phone}%")
            ->with('items.product') // প্রোডাক্ট ডিটেইলস সহ
            ->latest()
            ->take(5)
            ->get();

        return view('shop.tracking', compact('client', 'orders', 'phone'));
    }










}