<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_premium_themes')->default(false);
            $table->boolean('allow_payment_gateway')->default(false);
            $table->boolean('allow_delivery_integration')->default(false);
            $table->boolean('allow_facebook_messenger')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'allow_premium_themes',
                'allow_payment_gateway',
                'allow_delivery_integration',
                'allow_facebook_messenger'
            ]);
        });
    }
};
