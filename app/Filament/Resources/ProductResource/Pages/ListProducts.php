<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_woocommerce')
                ->label('Sync WooCommerce Products')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sync Products from WooCommerce')
                ->modalDescription('এটি আপনার ওয়ার্ডপ্রেস সাইট থেকে এক ক্লিকে সব প্রোডাক্ট (সর্বোচ্চ ১০০টি) ইম্পোর্ট করবে। ছবিগুলো (ডেসক্রিপশনের ভেতরের ছবি সহ) ডাউনলোড হতে কিছুটা সময় লাগতে পারে, দয়া করে সেভ হওয়া পর্যন্ত অপেক্ষা করুন।')
                ->modalSubmitActionLabel('Yes, Start Sync')
                ->action(function () {
                    $user = auth()->user();
                    $client = $user->client;

                    if (!$client) {
                        Notification::make()->title('Error')->body('আপনার কোনো দোকান সেটআপ করা নেই!')->danger()->send();
                        return;
                    }

                    if (!$client->wc_store_url || !$client->wc_consumer_key || !$client->wc_consumer_secret) {
                        Notification::make()
                            ->title('WooCommerce Setup Missing!')
                            ->body('দয়া করে Shop Settings-এর Store Sync ট্যাব থেকে WooCommerce Store URL, Consumer Key এবং Secret সেটআপ করুন।')
                            ->danger()
                            ->send();
                        return;
                    }

                    $url = rtrim($client->wc_store_url, '/') . '/wp-json/wc/v3/products';
                    
                    try {
                        set_time_limit(300); // অনেক ছবি ডাউনলোড হতে সময় লাগতে পারে তাই Time limit বাড়ানো হলো

                        $response = Http::withoutVerifying()->timeout(60)->get($url, [
                            'consumer_key' => $client->wc_consumer_key,
                            'consumer_secret' => $client->wc_consumer_secret,
                            'per_page' => 100,
                        ]);

                        if ($response->successful()) {
                            $products = $response->json();
                            $count = 0;

                            foreach ($products as $item) {
                                if (empty($item['name'])) continue;

                                $wpId = $item['id'] ?? uniqid();
                                $sku = (!empty($item['sku'])) ? $item['sku'] : 'WP-' . $wpId;

                                // ==========================================
                                // 🔥 1. Thumbnail & Gallery Extract
                                // ==========================================
                                $allImageUrls = [];
                                if (!empty($item['images']) && is_array($item['images'])) {
                                    foreach ($item['images'] as $img) {
                                        if (is_array($img) && !empty($img['src'])) {
                                            $allImageUrls[] = $img['src'];
                                        } elseif (is_string($img)) {
                                            $allImageUrls[] = $img;
                                        }
                                    }
                                }
                                if (!empty($item['image_url']) && is_string($item['image_url'])) $allImageUrls[] = $item['image_url'];
                                if (!empty($item['thumbnail']) && is_string($item['thumbnail'])) $allImageUrls[] = $item['thumbnail'];

                                $allImageUrls = array_values(array_unique(array_filter($allImageUrls)));

                                // ==========================================
                                // 🔥 2. Thumbnail & Gallery Download
                                // ==========================================
                                $thumbnailPath = null;
                                $galleryPaths = [];

                                if (!empty($allImageUrls)) {
                                    foreach ($allImageUrls as $index => $imageUrl) {
                                        try {
                                            $imageResponse = Http::withoutVerifying()->timeout(20)->get($imageUrl);
                                            if ($imageResponse->successful()) {
                                                $ext = explode('?', pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?? 'jpg')[0];
                                                if (!$ext) $ext = 'jpg';
                                                
                                                if ($index === 0) {
                                                    $filename = 'products/thumbnails/sync_' . time() . '_' . uniqid() . '.' . $ext;
                                                    Storage::disk('public')->put($filename, $imageResponse->body());
                                                    $thumbnailPath = $filename;
                                                } else {
                                                    if (count($galleryPaths) < 4) {
                                                        $filename = 'products/gallery/sync_' . time() . '_' . uniqid() . '.' . $ext;
                                                        Storage::disk('public')->put($filename, $imageResponse->body());
                                                        $galleryPaths[] = $filename;
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            Log::error("Bulk Sync Image Error for SKU {$sku}: " . $e->getMessage());
                                        }
                                    }
                                }

                                // ==========================================
                                // 🔥 3. Description HTML Image Parser & Downloader
                                // ==========================================
                                $description = $item['description'] ?? '';
                                
                                if (!empty($description)) {
                                    $description = preg_replace_callback('/<img[^>]+>/i', function($matches) {
                                        $imgTag = $matches[0];
                                        
                                        // img tag theke src khuje ber kora
                                        if (preg_match('/src=(["\'])(.*?)\1/i', $imgTag, $srcMatches)) {
                                            $originalUrl = $srcMatches[2];
                                            
                                            if (str_starts_with($originalUrl, 'http')) {
                                                try {
                                                    $imgRes = Http::withoutVerifying()->timeout(30)->get($originalUrl);
                                                    if ($imgRes->successful()) {
                                                        $ext = explode('?', pathinfo(parse_url($originalUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?? 'jpg')[0];
                                                        if (!$ext) $ext = 'jpg';
                                                        
                                                        // Description er chobigulo alada folder e save hobe
                                                        $filename = 'products/description/desc_' . time() . '_' . uniqid() . '.' . $ext;
                                                        Storage::disk('public')->put($filename, $imgRes->body());
                                                        
                                                        $newUrl = asset('storage/' . $filename);
                                                        
                                                        // HTML e purono link ke notun link diye replace kora
                                                        $imgTag = str_replace($originalUrl, $newUrl, $imgTag);
                                                        
                                                        // WordPress er extra size tag gulo muche fela jate vul koreo odik theke load na hoy
                                                        $imgTag = preg_replace('/srcset=(["\']).*?\1/i', '', $imgTag);
                                                        $imgTag = preg_replace('/sizes=(["\']).*?\1/i', '', $imgTag);
                                                    }
                                                } catch (\Exception $e) {
                                                    // Download fail korle ager link e theke jabe
                                                }
                                            }
                                        }
                                        return $imgTag;
                                    }, $description);
                                }

                                // ==========================================
                                // 🔥 4. Database Save Logic
                                // ==========================================
                                $existingProduct = Product::where('client_id', $client->id)->where('sku', $sku)->first();
        
                                $finalThumbnail = $thumbnailPath ?? ($existingProduct->thumbnail ?? null);
                                $finalGallery = !empty($galleryPaths) ? $galleryPaths : ($existingProduct->gallery ?? null);

                                $regularPrice = !empty($item['regular_price']) ? $item['regular_price'] : (!empty($item['price']) ? $item['price'] : 0);
                                $salePrice = !empty($item['sale_price']) ? $item['sale_price'] : null;
                                $stockQuantity = (isset($item['stock_quantity']) && $item['stock_quantity'] !== '') ? $item['stock_quantity'] : 100;

                                Product::updateOrCreate(
                                    [
                                        'client_id' => $client->id,
                                        'sku' => $sku
                                    ],
                                    [
                                        'name' => $item['name'],
                                        'slug' => Str::slug($item['name']) . '-' . Str::random(4),
                                        'regular_price' => $regularPrice,
                                        'sale_price' => $salePrice,
                                        'description' => $description, // 🔥 Update kora description save hobe
                                        'short_description' => $item['short_description'] ?? '',
                                        'stock_quantity' => $stockQuantity,
                                        'stock_status' => $stockQuantity > 0 ? 'in_stock' : 'out_of_stock',
                                        'thumbnail' => $finalThumbnail,
                                        'gallery' => $finalGallery,
                                    ]
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title('Sync Complete! 🎉')
                                ->body("মোট {$count} টি প্রোডাক্ট সফলভাবে ইম্পোর্ট করা হয়েছে (ডেসক্রিপশনের সব ছবি সহ)।")
                                ->success()
                                ->send();

                        } else {
                            Notification::make()
                                ->title('Sync Failed!')
                                ->body('WordPress থেকে ডাটা আনতে সমস্যা হচ্ছে। (Status: ' . $response->status() . ')')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Connection Error!')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}