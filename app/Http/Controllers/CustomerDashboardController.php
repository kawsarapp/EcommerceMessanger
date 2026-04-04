<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\LoyaltyPoint;
use App\Services\Shop\ShopClientService;

class CustomerDashboardController extends Controller
{
    protected $clientService;

    public function __construct(ShopClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');

        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);

        $customer = Auth::guard('customer')->user();
        
        // Match existing orders where customer_id matches OR phone matches (legacy)
        $orders = Order::where('client_id', $client->id)
            ->where(function($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere('customer_phone', $customer->phone);
            })
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        $loyaltyBalance = LoyaltyPoint::balanceFor($client->id, $customer->phone);

        return view('shop.customer.dashboard', compact('client', 'clean', 'baseUrl', 'customer', 'orders', 'loyaltyBalance'));
    }
}
