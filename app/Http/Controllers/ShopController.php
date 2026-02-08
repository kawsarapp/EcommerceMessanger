<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    /**
     * Display the shop page with products
     * 
     * @param string $slug - Client shop slug
     * @param Request $request - HTTP request with optional category filter
     * @return \Illuminate\View\View
     */
    public function show($slug, Request $request)
    {
        try {
            // ১. Slug দিয়ে Active Client খুঁজে বের করা (Cache ব্যবহার করে পারফরম্যান্স ইমপ্রুভ)
            $client = Cache::remember("client_{$slug}", 3600, function () use ($slug) {
                return Client::where('slug', $slug)
                    ->where('status', 'active')
                    ->firstOrFail();
            });

            // ২. প্রোডাক্ট কুয়েরি বিল্ডিং (পেজিনেশন সহ)
            $query = Product::where('client_id', $client->id)
                ->where('stock_status', 'in_stock')
                ->with(['category', 'variants']); // Eager loading for better performance

            // ৩. ক্যাটাগরি ফিল্টার চেক
            $selectedCategory = null;
            if ($request->has('category') && $request->category !== 'all' && $request->category !== '') {
                $selectedCategory = $request->category;
                $query->whereHas('category', function ($q) use ($selectedCategory) {
                    $q->where('slug', $selectedCategory);
                });
            }

            // ৪. সার্চ ফিল্টার চেক (নতুন ফিচার)
            $searchQuery = null;
            if ($request->has('search') && $request->search !== '') {
                $searchQuery = $request->search;
                $query->where(function($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                      ->orWhere('description', 'like', "%{$searchQuery}%")
                      ->orWhere('sku', 'like', "%{$searchQuery}%");
                });
            }

            // ৫. সর্টিং অপশন (নতুন ফিচার)
            $sortBy = $request->get('sort', 'latest');
            switch ($sortBy) {
                case 'price_low':
                    $query->orderBy('sale_price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('sale_price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }

            // ৬. প্রোডাক্ট পেজিনেশন (প্রতি পেজে 20 প্রোডাক্ট)
            $perPage = 20;
            $products = $query->paginate($perPage);

            // ৭. ক্যাটাগরি লোডিং (শুধুমাত্র যেসব ক্যাটাগরিতে প্রোডাক্ট আছে)
            $categories = Cache::remember("categories_{$client->id}", 1800, function () use ($client) {
                return Category::whereHas('products', function ($q) use ($client) {
                    $q->where('client_id', $client->id)
                      ->where('stock_status', 'in_stock');
                })->withCount(['products as product_count' => function($q) use ($client) {
                    $q->where('client_id', $client->id)
                      ->where('stock_status', 'in_stock');
                }])->get();
            });

            // ৮. স্ট্যাটস ক্যালকুলেশন (নতুন ফিচার)
            $totalProducts = Product::where('client_id', $client->id)
                ->where('stock_status', 'in_stock')
                ->count();
            
            $filteredProductsCount = $products->total();

            // ৯. ফিল্টার ইনফো প্রিপেয়ার
            $filterInfo = [
                'category' => $selectedCategory,
                'search' => $searchQuery,
                'sort' => $sortBy,
                'total' => $filteredProductsCount,
                'total_all' => $totalProducts
            ];

            // ১০. ভিউ রিটার্ন করা
            return view('shop.index', compact(
                'client', 
                'products', 
                'categories', 
                'filterInfo'
            ));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Client not found: {$slug}");
            abort(404, 'Shop not found or inactive');
        } catch (\Exception $e) {
            Log::error("ShopController Error: " . $e->getMessage());
            abort(500, 'Something went wrong. Please try again later.');
        }
    }

    /**
     * AJAX Load More Products (নতুন ফিচার)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadMore(Request $request)
    {
        try {
            $client = Client::where('slug', $request->slug)
                ->where('status', 'active')
                ->firstOrFail();

            $query = Product::where('client_id', $client->id)
                ->where('stock_status', 'in_stock')
                ->with(['category']);

            // ক্যাটাগরি ফিল্টার
            if ($request->has('category') && $request->category !== 'all') {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            // সার্চ ফিল্টার
            if ($request->has('search') && $request->search !== '') {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('description', 'like', "%{$request->search}%");
                });
            }

            // সর্টিং
            $sortBy = $request->get('sort', 'latest');
            switch ($sortBy) {
                case 'price_low':
                    $query->orderBy('sale_price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('sale_price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }

            $page = $request->get('page', 1);
            $perPage = 20;
            $products = $query->paginate($perPage, ['*'], 'page', $page);

            // প্রোডাক্ট ডেটা ফরম্যাট করে রিটার্ন
            $productsData = $products->map(function($product) use ($client) {
                $gallery = $product->gallery ? collect($product->gallery)->map(fn($img) => asset('storage/' . $img)) : [];
                
                // কালার এবং সাইজ প্রসেসিং
                $colors = is_string($product->colors) ? json_decode($product->colors, true) : ($product->colors ?? []);
                $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : ($product->sizes ?? []);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => number_format($product->sale_price ?? $product->regular_price),
                    'regular_price' => $product->regular_price,
                    'sale_price' => $product->sale_price,
                    'has_discount' => ($product->sale_price && $product->regular_price > $product->sale_price),
                    'discount_percentage' => $product->sale_price && $product->regular_price > $product->sale_price 
                        ? round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) 
                        : 0,
                    'thumbnail' => asset('storage/' . $product->thumbnail),
                    'gallery' => $gallery,
                    'colors' => $colors,
                    'sizes' => $sizes,
                    'sku' => $product->sku,
                    'url' => route('shop.show', $client->slug) . '?category=' . ($product->category->slug ?? '')
                ];
            });

            return response()->json([
                'success' => true,
                'products' => $productsData,
                'has_more' => $products->hasMorePages(),
                'next_page' => $products->currentPage() + 1,
                'total' => $products->total()
            ]);

        } catch (\Exception $e) {
            Log::error("Load More Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load more products'
            ], 500);
        }
    }

    /**
     * প্রোডাক্ট ডিটেইলস পেজ (নতুন ফিচার)
     * 
     * @param string $slug
     * @param string $productSlug
     * @return \Illuminate\View\View
     */
    public function productDetail($slug, $productSlug)
    {
        try {
            $client = Client::where('slug', $slug)
                ->where('status', 'active')
                ->firstOrFail();

            $product = Product::where('client_id', $client->id)
                ->where('slug', $productSlug)
                ->with(['category', 'relatedProducts'])
                ->firstOrFail();

            // রিলেটেড প্রোডাক্টস (একই ক্যাটাগরির)
            $relatedProducts = Product::where('client_id', $client->id)
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('stock_status', 'in_stock')
                ->latest()
                ->limit(8)
                ->get();

            return view('shop.product-detail', compact('client', 'product', 'relatedProducts'));

        } catch (\Exception $e) {
            Log::error("Product Detail Error: " . $e->getMessage());
            abort(404);
        }
    }

    /**
     * ক্যাটাগরি ওয়াইজ প্রোডাক্ট কাউন্ট আপডেট (AJAX)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryCounts(Request $request)
    {
        try {
            $client = Client::where('slug', $request->slug)->firstOrFail();
            
            $categories = Category::whereHas('products', function ($q) use ($client) {
                $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
            })->withCount(['products as product_count' => function($q) use ($client) {
                $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
            }])->get();

            return response()->json([
                'success' => true,
                'categories' => $categories->map(function($cat) {
                    return [
                        'id' => $cat->id,
                        'slug' => $cat->slug,
                        'name' => $cat->name,
                        'count' => $cat->product_count
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}