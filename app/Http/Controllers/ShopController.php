<?php

namespace App\Http\Controllers;

use App\Services\Shop\ShopClientService;
use App\Services\Shop\ShopProductService;
use App\Services\Shop\ShopOrderTrackingService;

// 🔥 আমরা যে নতুন Trait গুলো বানিয়েছি, সেগুলো ইম্পোর্ট করছি
use App\Traits\ShopStorefrontTrait;
use App\Traits\ShopCheckoutTrait;
use App\Traits\ShopTrackingTrait;

class ShopController extends Controller
{
    // ৩টি ট্রেইট একসাথে ব্যবহার করা হলো
    use ShopStorefrontTrait, ShopCheckoutTrait, ShopTrackingTrait;

    protected $clientService;
    protected $productService;
    protected $trackingService;

    public function __construct(
        ShopClientService $clientService,
        ShopProductService $productService,
        ShopOrderTrackingService $trackingService
    ) {
        $this->clientService = $clientService;
        $this->productService = $productService;
        $this->trackingService = $trackingService;
    }

    // 🔥 Helper: ডাইনামিক থিম ভিউ রিটার্ন করার ফাংশন (Multi-Theme)
    protected function themeView($client, $viewName, $data = [])
    {
        $theme = $client->theme_name ?? 'default';
        return view("shop.themes.{$theme}.{$viewName}", $data);
    }
}