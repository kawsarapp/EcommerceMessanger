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

        // ক্যাটাগরি ফিল্টার
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
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

        return $query->with('category')->paginate(12)->withQueryString();
    }

    /**
     * সাইডবার ক্যাটাগরি এবং প্রোডাক্ট কাউন্ট
     */
    public function getSidebarCategories($clientId)
    {
        return Category::whereHas('products', function ($q) use ($clientId) {
            $q->where('client_id', $clientId)->where('stock_status', 'in_stock');
        })->withCount(['products' => function ($q) use ($clientId) {
            $q->where('client_id', $clientId)->where('stock_status', 'in_stock');
        }])->orderBy('name')->get();
    }

    /**
     * সিঙ্গেল প্রোডাক্ট ফেচ করা
     */
    public function getProductBySlug($clientId, $productSlug)
    {
        return Product::where('client_id', $clientId)
            ->where('slug', $productSlug)
            ->with(['category'])
            ->first();
    }

    /**
     * রিলেটেড প্রোডাক্ট ফেচ করা
     */
    public function getRelatedProducts($clientId, $categoryId, $excludeId)
    {
        return Product::where('client_id', $clientId)
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->where('stock_status', 'in_stock')
            ->inRandomOrder()
            ->take(4)
            ->get();
    }
}