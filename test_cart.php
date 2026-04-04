<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$req = Illuminate\Http\Request::create('http://bangladeshmail24.com/cart/add', 'POST', ['product_id'=>1, 'qty'=>1]);
try {
    $ctrl = app()->make(App\Http\Controllers\ShopController::class);
    $res = $ctrl->addToCart($req, 'bangladeshmail24');
    echo $res->getContent();
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
