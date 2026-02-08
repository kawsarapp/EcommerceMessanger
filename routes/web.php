<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\FacebookConnectController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/shop/{slug}/product/{productSlug}', [ShopController::class, 'productDetail'])->name('shop.product');
Route::post('/shop/load-more', [ShopController::class, 'loadMore'])->name('shop.load-more');
Route::get('/shop/category-counts', [ShopController::class, 'getCategoryCounts'])->name('shop.category-counts');


Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback']);


