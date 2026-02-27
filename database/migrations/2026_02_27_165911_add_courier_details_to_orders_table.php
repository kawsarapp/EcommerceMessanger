<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Courier tracking er jonno notun column
            $table->string('courier_name')->nullable()->after('order_status'); // steadfast, pathao, redx
            $table->string('tracking_code')->nullable()->after('courier_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_name', 'tracking_code']);
        });
    }
};