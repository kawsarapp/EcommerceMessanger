<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'client_id',
        'category_id', // ✅ Correct Foreign Key
        'name',
        'slug',
        'sub_category',
        'brand',
        'tags',
        'regular_price',
        'sale_price',
        'discount_type',
        'tax',
        'currency',
        'description',
        'short_description',
        'long_description',
        'key_features',
        'thumbnail',
        'gallery',
        'video_url',
        'sku',
        'stock_quantity',
        'stock_status',
        'colors',
        'sizes',
        'material',
        'avg_rating',
        'total_reviews',
        'weight',
        'dimensions',
        'shipping_class',
        'meta_title',
        'meta_description',
        'warranty',
        'return_policy',
        'is_featured',
    ];

    // ✅ Casts for Arrays & Numbers
    protected $casts = [
        'key_features' => 'array',
        'gallery' => 'array',
        'colors' => 'array',
        'sizes' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    protected $attributes = [
        'regular_price' => 0,
        'stock_status' => 'in_stock',
    ];

    // 🔥 Auto-generate Slug (অটোমেটিক লিংক তৈরি করবে)
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                // নাম থেকে স্লাগ + ইউনিক কোড (যাতে ডুপ্লিকেট না হয়)
                $product->slug = Str::slug($product->name) . '-' . Str::random(6);
            }
        });
    }

    // 🔥 Model Events: Publish ba update korar somoy auto watermark hobe
    protected static function booted()
    {
        // ১. Jokhon notun product publish (Create) kora hobe
        static::created(function ($product) {
            self::applyWatermark($product->thumbnail, $product->sku);
            self::applyWatermarkToGallery($product->gallery, $product->sku);
        });

        // ২. Jokhon purono product edit/update kora hobe
        static::updated(function ($product) {
            if ($product->wasChanged('thumbnail')) {
                self::applyWatermark($product->thumbnail, $product->sku);
            }

            if ($product->wasChanged('gallery')) {
                $oldGallery = is_string($product->getOriginal('gallery')) ? json_decode($product->getOriginal('gallery'), true) : $product->getOriginal('gallery');
                $newGallery = is_string($product->gallery) ? json_decode($product->gallery, true) : $product->gallery;
                $oldGallery = is_array($oldGallery) ? $oldGallery : [];
                $newGallery = is_array($newGallery) ? $newGallery : [];
                $addedImages = array_diff($newGallery, $oldGallery);
                self::applyWatermarkToGallery($addedImages, $product->sku);
            }
        });
    }

    public static function applyWatermark($path, $sku)
    {
        if (!$path || !$sku || $sku === 'N/A')
            return;

        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath))
            return;

        try {
            // Intervention Image v3 implementation without Facades
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $img = $manager->read($fullPath);

            $watermarkText = 'SKU: ' . $sku;

            // Note: If fonts/roboto.ttf is missing, this might throw an error, 
            // but we are catching \Throwable so it won't crash the server.
            $img->text($watermarkText, $img->width() - 40, $img->height() - 40, function ($font) {
                if (file_exists(public_path('fonts/roboto.ttf'))) {
                    $font->filename(public_path('fonts/roboto.ttf'));
                } elseif (file_exists(public_path('fonts/arial.ttf'))) {
                    $font->filename(public_path('fonts/arial.ttf'));
                }
                
                $font->size(90);
                $font->color('rgba(0, 0, 0, 0.7)'); // Added opacity so it looks like a watermark
                $font->align('right');
                $font->valign('bottom');
            });

            $img->save($fullPath);
        }
        catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Watermark Error on Publish: " . $e->getMessage());
        }
    }

    public static function applyWatermarkToGallery($gallery, $sku)
    {
        if (empty($gallery))
            return;

        $galleryArray = is_string($gallery) ? json_decode($gallery, true) : $gallery;
        if (!is_array($galleryArray))
            return;

        foreach ($galleryArray as $path) {
            self::applyWatermark($path, $sku);
        }
    }

    // ===========================
    // 🔗 Relationships
    // ===========================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ===========================
    // 🛠 Accessors
    // ===========================

    public function getImageUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : asset('images/default-product.png');
    }
}