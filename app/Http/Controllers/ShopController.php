<?php

namespace App\Http\Controllers;

use App\Services\Shop\ShopClientService;
use App\Services\Shop\ShopProductService;
use App\Services\Shop\ShopOrderTrackingService;

// 🔥 আমরা যে নতুন Trait গুলো বানিয়েছি, সেগুলো ইম্পোর্ট করছি
use App\Traits\ShopStorefrontTrait;
use App\Traits\ShopCheckoutTrait;
use App\Traits\ShopTrackingTrait;
use App\Traits\ShopCartTrait;
use App\Traits\ShopFeaturesTrait;

class ShopController extends Controller
{
    use ShopStorefrontTrait, ShopCheckoutTrait, ShopTrackingTrait, ShopCartTrait, ShopFeaturesTrait;

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

    protected function themeView($client, $viewName, $data = [])
    {
        $theme = $client->theme_name ?? 'default';

        $menus = \App\Models\Menu::with(['items' => fn($q) => $q->orderBy('sort_order')])
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('location');

        // Load Categories globally for top header nav
        $categories = $this->productService->getSidebarCategories($client->id);

        $data = array_merge($data, [
            'primaryMenu' => $menus->get('primary_header'),
            'footerMenu1' => $menus->get('footer_1'),
            'footerMenu2' => $menus->get('footer_2'),
            'footerMenu3' => $menus->get('footer_3'),
            'mobileNavMenu' => $menus->get('mobile_nav'),
            'categories'  => $categories,
        ]);

        return view("shop.themes.{$theme}.{$viewName}", $data);
    }
}