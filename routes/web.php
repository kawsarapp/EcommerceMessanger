<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\FacebookConnectController;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');


Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback']);