<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageOptimizer;

class OptimizeSystem extends Command
{
    protected $signature   = 'system:optimize
                                {--images : পুরনো ইমেজগুলো WebP তে compress করো}
                                {--db : Database cleanup ও optimize করো}
                                {--all : সব কিছু}';

    protected $description = 'ইমেজ compress, database cleanup ও optimize করবে।';

    public function handle(): int
    {
        $this->info('🚀 EcommerceMessanger System Optimizer');
        $this->newLine();

        if ($this->option('all') || $this->option('db')) {
            $this->optimizeDatabase();
        }

        if ($this->option('all') || $this->option('images')) {
            $this->optimizeImages();
        }

        if (!$this->option('all') && !$this->option('db') && !$this->option('images')) {
            $this->info('ব্যবহার: php artisan system:optimize --all');
            $this->info('         php artisan system:optimize --db');
            $this->info('         php artisan system:optimize --images');
        }

        return self::SUCCESS;
    }

    private function optimizeDatabase(): void
    {
        $this->info('📊 Database Optimization শুরু হচ্ছে...');

        // 1. MySQL ANALYZE (query planner statistics update)
        // এটি ডাটাবেসকে ফাস্ট করে, কিন্তু কোনো ডেটা ডিলিট করে না
        $tables = ['products', 'orders', 'clients', 'reviews', 'conversations', 'messages'];
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE `{$table}`");
            } catch (\Exception $e) {
                // ignore
            }
        }
        $this->line('  ✅ ANALYZE TABLE সম্পন্ন হলো (No Data Deleted)');

        $this->info('  ✅ Database Optimization সম্পন্ন!');
        $this->newLine();
    }

    private function optimizeImages(): void
    {
        $this->info('🖼️  Image Optimization শুরু হচ্ছে...');

        $optimizer = new ImageOptimizer();

        $directories = [
            ['path' => 'products/thumbnails', 'preset' => 'product_thumbnail'],
            ['path' => 'products/gallery',    'preset' => 'product_gallery'],
            ['path' => 'shops/logos',         'preset' => 'shop_logo'],
            ['path' => 'shops/banners',       'preset' => 'shop_banner'],
        ];

        $totalSaved = 0;
        $totalProcessed = 0;

        foreach ($directories as $dir) {
            $this->line("  📁 {$dir['path']} optimize করছি...");
            $results = $optimizer->bulkOptimizeExisting($dir['path'], $dir['preset']);
            $totalSaved    += $results['saved_kb'];
            $totalProcessed += $results['processed'];
            $this->line("     - Processed: {$results['processed']}, Skipped: {$results['skipped']}, Saved: {$results['saved_kb']} KB");
        }

        $savedMb = round($totalSaved / 1024, 2);
        $this->info("  🎉 মোট {$totalProcessed}টি ইমেজ optimize হলো, {$savedMb} MB জায়গা বাঁচলো!");
        $this->newLine();
    }

    private function checkOrphanedRecords(): void
    {
        // Products যার client নেই
        $orphanProducts = DB::table('products')
            ->leftJoin('clients', 'products.client_id', '=', 'clients.id')
            ->whereNull('clients.id')
            ->count();

        if ($orphanProducts > 0) {
            $this->warn("  ⚠️  Orphaned products (client deleted): {$orphanProducts}টি");
        }
    }
}
