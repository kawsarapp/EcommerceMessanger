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
       
        return $this->themeView($client, 'tracking', compact('client', 'pages'));
    }

    public function trackOrderSubmit(Request $request, $slug = null)
    {
        $request->validate(['phone' => 'required|min:11']);

        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $orders = $this->trackingService->trackOrder($client->id, $request->phone);
        $pages = $this->clientService->getActivePages($client->id);
        $phone = $request->phone;

        return $this->themeView($client, 'tracking', compact('client', 'orders', 'phone', 'pages'));
    }
}