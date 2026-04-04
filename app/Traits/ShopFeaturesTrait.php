<?php
namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\StockAlertService;

trait ShopFeaturesTrait
{
    /**
     * Product Compare Page
     * URL: /compare?ids=1,2,3
     */
    public function comparePage(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');

        $ids      = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        $products = collect();

        if (!empty($ids)) {
            $products = Product::where('client_id', $client->id)
                ->whereIn('id', array_slice($ids, 0, 3))
                ->with('category')
                ->get();
        }

        // Collect all unique spec keys across selected products
        $specKeys = [];
        foreach ($products as $p) {
            $features = is_array($p->key_features) ? $p->key_features : json_decode($p->key_features ?? '[]', true);
            foreach ((array) $features as $feature) {
                if (is_string($feature)) $specKeys[] = $feature;
                elseif (isset($feature['label'])) $specKeys[] = $feature['label'];
            }
        }
        $specKeys = array_values(array_unique($specKeys));

        $pages = $this->clientService->getActivePages($client->id);

        return $this->themeView($client, 'compare', compact('client', 'products', 'specKeys', 'pages'));
    }

    /**
     * Stock Notify Me (AJAX POST)
     * Saves customer phone for back-in-stock SMS notification
     */
    public function stockNotify(Request $request, $slug = null)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'phone'      => 'required|string|min:11|max:15',
        ]);

        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) {
            return response()->json(['success' => false, 'message' => 'Invalid shop'], 404);
        }

        $product = Product::where('client_id', $client->id)->where('id', $request->product_id)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $saved = app(StockAlertService::class)->saveNotifyRequest(
            $client,
            $product,
            $request->phone,
            $request->name ?? null
        );

        return response()->json([
            'success' => $saved,
            'message' => $saved ? 'আপনাকে SMS-এ জানানো হবে!' : 'সমস্যা হয়েছে, আবার চেষ্টা করুন।',
        ]);
    }
}
