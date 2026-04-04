<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController {
    public function intercept(\Throwable $e) {
        file_put_contents('cart_error_log.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
