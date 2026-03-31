<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Models\Category;

class ShopProductService
{
    /**
     * প্রোডাক্ট ফিল্টারিং এবং সর্টিং
     */
    public function getFilteredProducts($request, $clientId)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        // সার্চ ফিল্টার
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // ক্যাটাগরি ফিল্টার — global অথবা এই shop এর private category slug দিয়ে ফিল্টার
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request, $clientId) {
                $q->where('slug', $request->category)
                  ->where(function ($q2) use ($clientId) {
                      // Global category (super admin তৈরি) অথবা এই shop এর নিজের private category
                      $q2->where('is_global', true)
                         ->orWhere('client_id', $clientId);
                  });
            });
        }

        // প্রাইস ফিল্টার
        if ($request->filled('min_price')) $query->where('regular_price', '>=', $request->min_price);
        if ($request->filled('max_price')) $query->where('regular_price', '<=', $request->max_price);

        // সর্টিং
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
            default:
                $query->latest();
                break;
        }

        return $query->with(['category', 'client'])->paginate(12)->withQueryString();
    }

    /**
     * সাইডবার ক্যাটাগরি — global categories + এই shop এর নিজের private categories
     */
    public function getSidebarCategories($clientId)
    {
        return Category::where(function ($q) use ($clientId) {
                // Global categories (সব seller এর জন্য) অথবা এই shop এর নিজের private categories
                $q->where('is_global', true)
                  ->orWhere('client_id', $clientId);
            })
            ->where('is_visible', true)
            ->withCount(['products' => function ($q) use ($clientId) {
                $q->where('client_id', $clientId)->where('stock_status', 'in_stock');
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name')
            ->get();
    }

    /**
     * সিঙ্গেল প্রোডাক্ট ফেচ করা — client_id দিয়ে verify করা হচ্ছে
     */
    public function getProductBySlug($clientId, $productSlug)
    {
        return Product::where('client_id', $clientId) // 🔒 SECURITY: product must belong to this shop
            ->where('slug', $productSlug)
            ->with(['category', 'client', 'reviews' => function($q) {
                $q->where('is_visible', true)->latest();
            }])
            ->first();
    }

    /**
     * রিলেটেড প্রোডাক্ট — একই shop এবং একই category, অন্য seller এর প্রোডাক্ট কখনো না
     */
    public function getRelatedProducts($clientId, $categoryId, $excludeId)
    {
        return Product::where('client_id', $clientId)  // 🔒 SECURITY: same shop only
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->where('stock_status', 'in_stock')
            ->with(['category', 'client'])
            ->inRandomOrder()
            ->take(4)
            ->get();
    }
}