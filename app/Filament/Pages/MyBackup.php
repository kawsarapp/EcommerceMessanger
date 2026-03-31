<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class MyBackup extends Page
{
    protected static ?string $navigationIcon   = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup  = '⚙️ Settings & Tools';
    protected static ?string $navigationLabel  = 'My Business Backup';
    protected static ?int    $navigationSort   = 11;
    protected static string  $view             = 'filament.pages.my-backup';

    // Super admin এর জন্য নয় — seller এবং staff দেখতে পাবে
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return false; // Super admin এর জন্য BackupManager আছে
        return $user->client && $user->client->hasActivePlan();
    }

    protected function getHeaderActions(): array
    {
        $client = $this->getClient();

        return [
            Action::make('downloadMyBackup')
                ->label('⬇️ Download My Business Data')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Download Business Backup')
                ->modalDescription('আপনার সকল products, orders, customers, categories, pages — সব কিছু একটি ZIP ফাইলে ডাউনলোড হবে।')
                ->action(fn () => $this->downloadMyZip()),

            Action::make('downloadCsv')
                ->label('📊 Orders CSV')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn () => $this->downloadOrdersCsv()),

            Action::make('downloadProductsCsv')
                ->label('📦 Products CSV')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->action(fn () => $this->downloadProductsCsv()),
        ];
    }

    private function getClient(): ?Client
    {
        return auth()->user()?->client;
    }

    /**
     * সব business data একটি ZIP এ ডাউনলোড করা
     */
    public function downloadMyZip()
    {
        $client = $this->getClient();
        if (!$client) {
            Notification::make()->danger()->title('No shop found.')->send();
            return;
        }

        $safeName  = preg_replace('/[^a-z0-9\-]/i', '-', $client->shop_name);
        $zipName   = "backup-{$safeName}-" . now()->format('Y-m-d') . '.zip';
        $zipPath   = storage_path('app/backup-temp/' . $zipName);
        @mkdir(dirname($zipPath), 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()->danger()->title('ZIP তৈরি করা সম্ভব হয়নি।')->send();
            return;
        }

        // 1. Orders CSV
        $zip->addFromString('orders.csv', $this->buildOrdersCsv($client->id));

        // 2. Products CSV
        $zip->addFromString('products.csv', $this->buildProductsCsv($client->id));

        // 3. Categories CSV
        $zip->addFromString('categories.csv', $this->buildCategoriesCsv($client->id));

        // 4. Shop Info JSON
        $shopInfo = $client->only([
            'shop_name', 'slug', 'custom_domain', 'phone', 'email', 'address',
            'facebook_url', 'instagram_url', 'meta_title', 'meta_description', 'theme_name',
        ]);
        $zip->addFromString('shop-info.json', json_encode($shopInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 5. Uploaded media files (images only)
        $uploadPaths = [
            "shops/logos",
            "shops/banners",
            "shops/hero-banners",
            "products/thumbnails",
            "products/gallery",
            "categories/banners",
        ];
        foreach ($uploadPaths as $dir) {
            $fullPath = storage_path("app/public/{$dir}");
            if (!is_dir($fullPath)) continue;
            $files = glob($fullPath . '/*');
            foreach (($files ?: []) as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, "media/{$dir}/" . basename($file));
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * শুধু Orders CSV ডাউনলোড
     */
    public function downloadOrdersCsv()
    {
        $client = $this->getClient();
        if (!$client) return;

        $filename = 'orders-' . now()->format('Y-m-d') . '.csv';
        $csv = $this->buildOrdersCsv($client->id);

        return response()->streamDownload(fn () => print($csv), $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * শুধু Products CSV ডাউনলোড
     */
    public function downloadProductsCsv()
    {
        $client = $this->getClient();
        if (!$client) return;

        $filename = 'products-' . now()->format('Y-m-d') . '.csv';
        $csv = $this->buildProductsCsv($client->id);

        return response()->streamDownload(fn () => print($csv), $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ─── CSV Builders ────────────────────────────────────

    private function buildOrdersCsv(int $clientId): string
    {
        $orders = DB::table('orders')
            ->where('client_id', $clientId)
            ->select('id', 'customer_name', 'customer_phone', 'customer_address', 'total_price', 'status', 'payment_method', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        $rows = ["Order ID,Customer Name,Phone,Address,Total (৳),Status,Payment,Date"];
        foreach ($orders as $o) {
            $rows[] = implode(',', [
                $o->id,
                '"' . str_replace('"', '""', $o->customer_name ?? '') . '"',
                '"' . ($o->customer_phone ?? '') . '"',
                '"' . str_replace('"', '""', $o->customer_address ?? '') . '"',
                $o->total_price ?? 0,
                $o->status ?? '',
                $o->payment_method ?? '',
                $o->created_at ?? '',
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\n", $rows); // UTF-8 BOM for Excel
    }

    private function buildProductsCsv(int $clientId): string
    {
        $products = DB::table('products')
            ->where('client_id', $clientId)
            ->select('id', 'name', 'sku', 'sale_price', 'regular_price', 'stock_quantity', 'stock_status', 'brand', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        $rows = ["Product ID,Name,SKU,Sale Price (৳),Regular Price (৳),Stock Qty,Status,Brand,Date"];
        foreach ($products as $p) {
            $rows[] = implode(',', [
                $p->id,
                '"' . str_replace('"', '""', $p->name ?? '') . '"',
                '"' . ($p->sku ?? '') . '"',
                $p->sale_price ?? 0,
                $p->regular_price ?? 0,
                $p->stock_quantity ?? 0,
                $p->stock_status ?? '',
                '"' . ($p->brand ?? '') . '"',
                $p->created_at ?? '',
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\n", $rows);
    }

    private function buildCategoriesCsv(int $clientId): string
    {
        $cats = DB::table('categories')
            ->where(function ($q) use ($clientId) {
                $q->where('is_global', true)->orWhere('client_id', $clientId);
            })
            ->select('id', 'name', 'slug', 'is_global', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        $rows = ["ID,Name,Slug,Is Global,Sort Order"];
        foreach ($cats as $c) {
            $rows[] = implode(',', [
                $c->id,
                '"' . str_replace('"', '""', $c->name ?? '') . '"',
                $c->slug ?? '',
                $c->is_global ? 'Yes' : 'No',
                $c->sort_order ?? 0,
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\n", $rows);
    }

    public function getTitle(): string
    {
        return '📦 My Business Backup';
    }
}
