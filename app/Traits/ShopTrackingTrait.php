<?php
namespace App\Traits;

use Illuminate\Http\Request;

trait ShopTrackingTrait
{
    public function trackOrder(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $pages = $this->clientService->getActivePages($client->id);
        
        if ($request->filled('order_id')) {
            $orders = $this->trackingService->trackOrder($client->id, $request->order_id);
            $orderId = $request->order_id;
            return $this->themeView($client, 'tracking', compact('client', 'orders', 'orderId', 'pages'));
        }
       
        return $this->themeView($client, 'tracking', compact('client', 'pages'));
    }

    public function trackOrderSubmit(Request $request, $slug = null)
    {
        $request->validate(['order_id' => 'required|numeric|min:1']);

        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $orders = $this->trackingService->trackOrder($client->id, $request->order_id);
        $pages = $this->clientService->getActivePages($client->id);
        $orderId = $request->order_id;

        return $this->themeView($client, 'tracking', compact('client', 'orders', 'orderId', 'pages'));
    }
}