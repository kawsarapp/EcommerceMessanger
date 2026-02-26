<?php

namespace App\Filament\Imports;

use App\Models\Product;
use App\Models\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Product Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
                
            ImportColumn::make('sku')
                ->label('SKU / Code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
                
            ImportColumn::make('category_name')
                ->label('Category Name')
                ->rules(['nullable', 'max:255'])
                ->example('Panjabi'),
                
            ImportColumn::make('regular_price')
                ->label('Regular Price')
                ->numeric()
                ->rules(['nullable', 'numeric']),
                
            ImportColumn::make('sale_price')
                ->label('Sale Price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
                
            ImportColumn::make('stock_quantity')
                ->label('Stock Quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
                
            ImportColumn::make('description')
                ->label('Description')
                ->rules(['nullable']),

            // 🔥 নতুন কলাম: Image URL
            ImportColumn::make('image_url')
                ->label('Image URL (Link)')
                ->rules(['nullable', 'url'])
                ->example('https://example.com/image.jpg'),
        ];
    }

    public function resolveRecord(): ?Product
    {
        $client = auth()->user()->client;

        return Product::firstOrNew([
            'sku' => $this->data['sku'],
            'client_id' => $client->id,
        ]);
    }

    protected function beforeSave(): void
    {
        $client = auth()->user()->client;

        $this->record->client_id = $client->id;

        if (!$this->record->slug) {
            $this->record->slug = Str::slug($this->record->name) . '-' . Str::random(5);
        }

        if (!empty($this->data['category_name'])) {
            $category = Category::firstOrCreate([
                'client_id' => $client->id,
                'name' => trim($this->data['category_name'])
            ]);
            $this->record->category_id = $category->id;
        }

        $this->record->stock_status = $this->record->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
    }

    // 🔥 ছবি ডাউনলোড ও সেভ করার লজিক
    protected function afterSave(): void
    {
        // যদি এক্সেল শিটে image_url থাকে এবং প্রোডাক্টের আগে থেকে কোনো ছবি না থাকে
        if (!empty($this->data['image_url']) && empty($this->record->thumbnail)) {
            try {
                // লিংক থেকে ছবি ডাউনলোড করা
                $response = Http::timeout(10)->get($this->data['image_url']);

                if ($response->successful()) {
                    // ছবির জন্য ইউনিক নাম তৈরি করা
                    $extension = explode('?', pathinfo($this->data['image_url'], PATHINFO_EXTENSION))[0] ?: 'jpg';
                    $imageName = 'products/thumbnails/' . Str::random(10) . '.' . $extension;

                    // স্টোরেজে সেভ করা (public disk)
                    Storage::disk('public')->put($imageName, $response->body());

                    // ডাটাবেস আপডেট করা
                    $this->record->update(['thumbnail' => $imageName]);
                }
            } catch (\Exception $e) {
                // কোনো কারণে ছবি ডাউনলোড না হলে লগ সেভ করে রাখবে, কিন্তু ইম্পোর্ট ক্র্যাশ করবে না
                Log::error("Bulk Import Image Error (SKU: {$this->data['sku']}): " . $e->getMessage());
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'আপনার প্রোডাক্ট ইম্পোর্ট সম্পন্ন হয়েছে। মোট ' . number_format($import->successful_rows) . ' টি প্রোডাক্ট সফলভাবে যুক্ত হয়েছে।';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' টি প্রোডাক্ট ইম্পোর্ট করতে ব্যর্থ হয়েছে।';
        }

        return $body;
    }
}