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
            // 🔥 NEW: Sync All Products Action Button
            Actions\Action::make('sync_woocommerce')
                ->label('Sync WooCommerce Products')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Sync Products from WooCommerce')
                ->modalDescription('এটি আপনার ওয়ার্ডপ্রেস সাইট থেকে এক ক্লিকে সব প্রোডাক্ট (সর্বোচ্চ ১০০টি) ইম্পোর্ট করবে। ছবিগুলো ডাউনলোড হতে কিছুটা সময় লাগতে পারে, দয়া করে সেভ হওয়া পর্যন্ত অপেক্ষা করুন।')
                ->modalSubmitActionLabel('Yes, Start Sync')
                ->action(function () {
                    $user = auth()->user();
                    $client = $user->client; // লগইন করা ইউজারের দোকান

                    if (!$client) {
                        Notification::make()->title('Error')->body('আপনার কোনো দোকান সেটআপ করা নেই!')->danger()->send();
                        return;
                    }

                    // চেক করা হচ্ছে API ডিটেইলস দেওয়া আছে কিনা
                    if (!$client->wc_store_url || !$client->wc_consumer_key || !$client->wc_consumer_secret) {
                        Notification::make()
                            ->title('WooCommerce Setup Missing!')
                            ->body('দয়া করে Shop Settings-এর Store Sync ট্যাব থেকে WooCommerce Store URL, Consumer Key এবং Secret সেটআপ করুন।')
                            ->danger()
                            ->send();
                        return;
                    }

                    $url = rtrim($client->wc_store_url, '/') . '/wp-json/wc/v3/products';
                    
                    try {
                        // Execution time বাড়ানো হলো যেন অনেক ইমেজ থাকলে Timeout না হয়
                        set_time_limit(120); 

                        $response = Http::withoutVerifying()->timeout(60)->get($url, [
                            'consumer_key' => $client->wc_consumer_key,
                            'consumer_secret' => $client->wc_consumer_secret,
                            'per_page' => 100, // এক সাথে ১০০ প্রোডাক্ট ইম্পোর্ট করবে
                        ]);

                        if ($response->successful()) {
                            $products = $response->json();
                            $count = 0;

                            foreach ($products as $item) {
                                if (empty($item['name'])) continue;

                                $wpId = $item['id'] ?? uniqid();
                                $sku = (!empty($item['sku'])) ? $item['sku'] : 'WP-' . $wpId;

                                // 🔥 Image URL Extraction
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

                                // 🔥 Image Download Logic
                                $thumbnailPath = null;
                                $galleryPaths = [];

                                if (!empty($allImageUrls)) {
                                    foreach ($allImageUrls as $index => $imageUrl) {
                                        try {
                                            $imageResponse = Http::withoutVerifying()->timeout(20)->get($imageUrl);
                                            if ($imageResponse->successful()) {
                                                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                                                if ($index === 0) {
                                                    $filename = 'products/thumbnails/sync_' . time() . '_' . uniqid() . '.' . $extension;
                                                    Storage::disk('public')->put($filename, $imageResponse->body());
                                                    $thumbnailPath = $filename;
                                                } else {
                                                    if (count($galleryPaths) < 4) {
                                                        $filename = 'products/gallery/sync_' . time() . '_' . uniqid() . '.' . $extension;
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

                                // 🔥 Database Save Logic
                                $existingProduct = Product::where('client_id', $client->id)->where('sku', $sku)->first();
        
                                $finalThumbnail = $thumbnailPath ?? ($existingProduct->thumbnail ?? null);
                                $finalGallery = !empty($galleryPaths) ? $galleryPaths : ($existingProduct->gallery ?? null);

                                Product::updateOrCreate(
                                    [
                                        'client_id' => $client->id,
                                        'sku' => $sku
                                    ],
                                    [
                                        'name' => $item['name'],
                                        'slug' => Str::slug($item['name']) . '-' . Str::random(4),
                                        'regular_price' => $item['regular_price'] ?? ($item['price'] ?? 0),
                                        'sale_price' => $item['sale_price'] ?? null,
                                        'description' => $item['description'] ?? '',
                                        'short_description' => $item['short_description'] ?? '',
                                        'stock_quantity' => $item['stock_quantity'] ?? 100,
                                        'stock_status' => ($item['stock_quantity'] ?? 1) > 0 ? 'in_stock' : 'out_of_stock',
                                        'thumbnail' => $finalThumbnail,
                                        'gallery' => $finalGallery,
                                    ]
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title('Sync Complete! 🎉')
                                ->body("মোট {$count} টি প্রোডাক্ট সফলভাবে ইম্পোর্ট করা হয়েছে।")
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
                            ->body('Error: ' . $e->getMessage()) // 🔥 আসল এরর মেসেজটি দেখাবে
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}