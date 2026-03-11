<?php
namespace App\Traits;

use Illuminate\Http\Request;

trait ShopStorefrontTrait
{
    public function show(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) abort(404, 'No Active Shop Found');

        $products = $this->productService->getFilteredProducts($request, $client->id);
        $theme = $client->theme_name ?? 'default';

        if ($request->ajax()) {
            return view("shop.themes.{$theme}.partials.product_list", compact('products'))->render();
        }

        $categories = $this->productService->getSidebarCategories($client->id);
        $pages = $this->clientService->getActivePages($client->id, true);

        return $this->themeView($client, 'index', compact('client', 'products', 'categories', 'pages'));
    }

    public function productDetails(Request $request, $slug = null, $productSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $productSlug = $productSlug ?? $slug; 
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client->exists) return redirect('/');

        $product = $this->productService->getProductBySlug($client->id, $productSlug);

        if (!$product) {
            if ($request->has('current_client')) return redirect('/');
            return $client->slug ? redirect()->route('shop.show', $client->slug) : redirect('/');
        }

        $relatedProducts = $this->productService->getRelatedProducts($client->id, $product->category_id, $product->id);
        $pages = $this->clientService->getActivePages($client->id);

        return $this->themeView($client, 'product', compact('client', 'product', 'relatedProducts', 'pages'));
    }

    public function showPage(Request $request, $slug = null, $pageSlug = null)
    {
        $result = $this->clientService->resolveDynamicPage($request, $slug, $pageSlug);

        if (isset($result['error'])) {
            if ($result['error'] === 'not_found') abort(404, 'Shop or Page Not Found');
            if ($result['error'] === 'redirect') return redirect($result['redirect_url']);
        }

        $client = $result['client'];
        $page = $result['page'];
        $pages = $this->clientService->getActivePages($client->id);

        return $this->themeView($client, 'page', compact('client', 'page', 'pages'));
    }

    public function loadMore(Request $request)
    {
        return $this->show($request, $request->slug);
    }
}