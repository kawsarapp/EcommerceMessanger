<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // ডিফল্ট কুরিয়ার কোনটি ব্যবহার করবে
            $table->string('default_courier')->nullable()->after('delivery_charge_outside'); // steadfast, pathao, redx
            
            // Steadfast API Credentials
            $table->string('steadfast_api_key')->nullable();
            $table->string('steadfast_secret_key')->nullable();
            
            // Pathao API Credentials
            $table->string('pathao_api_key')->nullable();
            $table->string('pathao_store_id')->nullable();
            
            // RedX API Credentials
            $table->string('redx_api_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'default_courier',
                'steadfast_api_key', 'steadfast_secret_key',
                'pathao_api_key', 'pathao_store_id',
                'redx_api_token'
            ]);
        });
    }
};