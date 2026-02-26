<?php

namespace App\Services\Shop;

use App\Models\Client;
use App\Models\Page;
use Illuminate\Http\Request;

class ShopClientService
{
    /**
     * নিরাপদ ক্লায়েন্ট ডিটেকশন (Safe Fallback)
     */
    public function getSafeClient(Request $request, $slug = null)
    {
        if ($request->has('current_client')) {
            return $request->current_client;
        }

        if ($slug) {
            $client = Client::where('slug', $slug)->where('status', 'active')->first();
            if ($client) return $client;
        }

        return Client::where('status', 'active')->first() ?? new Client();
    }

    /**
     * শপের একটিভ পেজগুলো (ফুটারের জন্য)
     */
    public function getActivePages($clientId, $specificSelect = false)
    {
        $query = Page::where('client_id', $clientId)->where('is_active', true);
        if ($specificSelect) {
            $query->select('title', 'slug');
        }
        return $query->get();
    }

    /**
     * ডাইনামিক পেজ রাউটিং (Custom Domain vs Path)
     */
    public function resolveDynamicPage(Request $request, $slug = null, $pageSlug = null)
    {
        $client = null;
        $actualPageSlug = null;
        $routeName = $request->route()->getName();

        if ($routeName === 'shop.page.custom') {
            if ($request->has('current_client')) {
                $client = $request->current_client;
                $actualPageSlug = $request->route('pageSlug') ?? $slug; 
            }
        } 
        elseif ($routeName === 'shop.page.slug') {
            $client = Client::where('slug', $slug)->where('status', 'active')->first();
            $actualPageSlug = $pageSlug;
        }

        if (!$client || !$actualPageSlug) {
            return ['error' => 'not_found'];
        }

        $page = Page::where('client_id', $client->id)
            ->where('slug', $actualPageSlug)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return [
                'error' => 'redirect',
                'redirect_url' => $request->has('current_client') ? route('home') : route('shop.show', $client->slug)
            ];
        }

        return ['client' => $client, 'page' => $page];
    }
}