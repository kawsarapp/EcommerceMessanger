<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizer
{
    private ImageManager $manager;

    // WebP supported browser গুলো এটি অনেক ছোট করে
    private const CONFIGS = [
        'product_thumbnail' => ['width' => 800,  'height' => 800,  'quality' => 78, 'format' => 'webp'],
        'product_gallery'   => ['width' => 1200, 'height' => 1200, 'quality' => 80, 'format' => 'webp'],
        'shop_logo'         => ['width' => 400,  'height' => 400,  'quality' => 85, 'format' => 'webp'],
        'shop_banner'       => ['width' => 1600, 'height' => 600,  'quality' => 80, 'format' => 'webp'],
        'default'           => ['width' => 1200, 'height' => 1200, 'quality' => 80, 'format' => 'webp'],
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * ইমেজ compress করে storage এ save করবে।
     * 
     * @param  string|\Illuminate\Http\UploadedFile  $source  Path বা UploadedFile
     * @param  string  $directory  যেমন: products/thumbnails
     * @param  string  $preset     self::CONFIGS এর key
     * @return string  নতুন file path (storage relative)
     */
    public function optimize(string|UploadedFile $source, string $directory, string $preset = 'default'): string
    {
        $config = self::CONFIGS[$preset] ?? self::CONFIGS['default'];

        // ইমেজ লোড করো
        if ($source instanceof UploadedFile) {
            $image = $this->manager->read($source->getRealPath());
        } else {
            $fullPath = storage_path('app/public/' . $source);
            if (!file_exists($fullPath)) return $source; // ফাইল নেই, যা আছে রিটার্ন
            $image = $this->manager->read($fullPath);
        }

        // Resize (aspect ratio maintain করে, crop করবে না)
        $image->scaleDown($config['width'], $config['height']);

        // Encode
        $encoded = $image->toWebp($config['quality']);

        // নতুন filename তৈরি
        $filename = Str::uuid() . '.webp';
        $path = trim($directory, '/') . '/' . $filename;

        // Storage এ save
        Storage::disk('public')->put($path, $encoded);

        return $path;
    }

    /**
     * বিদ্যমান সব পুরনো ইমেজ compress করবে (Artisan command দিয়ে চালানো হবে)
     */
    public function bulkOptimizeExisting(string $directory, string $preset = 'default'): array
    {
        $files = Storage::disk('public')->files($directory);
        $results = ['processed' => 0, 'skipped' => 0, 'saved_kb' => 0];

        foreach ($files as $file) {
            // ইতিমধ্যে webp হলে skip
            if (str_ends_with($file, '.webp')) {
                $results['skipped']++;
                continue;
            }

            try {
                $fullPath = storage_path('app/public/' . $file);
                $beforeSize = filesize($fullPath);

                $config = self::CONFIGS[$preset] ?? self::CONFIGS['default'];
                $image  = $this->manager->read($fullPath);
                $image->scaleDown($config['width'], $config['height']);
                $encoded = $image->toWebp($config['quality']);

                // पुराना ফাইল মুছে, নতুন webp দিয়ে replace করা
                $newPath = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME) . '.webp';
                Storage::disk('public')->put($newPath, $encoded);

                if ($newPath !== $file) {
                    Storage::disk('public')->delete($file);
                }

                $afterSize = Storage::disk('public')->size($newPath);
                $results['saved_kb'] += round(($beforeSize - $afterSize) / 1024, 2);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['skipped']++;
            }
        }

        return $results;
    }
}
