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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('show_stock')->default(true);
            $table->boolean('show_related_products')->default(true);
            $table->boolean('show_return_warranty')->default(true);
            $table->boolean('cod_active')->default(true);
            $table->boolean('partial_payment_active')->default(false);
            $table->boolean('full_payment_active')->default(false);
            $table->text('footer_text')->nullable();
            $table->json('footer_links')->nullable();
            $table->boolean('popup_active')->default(false);
            $table->string('popup_title')->nullable();
            $table->text('popup_description')->nullable();
            $table->string('popup_image')->nullable();
            $table->string('popup_link')->nullable();
            $table->dateTime('popup_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'show_stock',
                'show_related_products',
                'show_return_warranty',
                'cod_active',
                'partial_payment_active',
                'full_payment_active',
                'footer_text',
                'footer_links',
                'popup_active',
                'popup_title',
                'popup_description',
                'popup_image',
                'popup_link',
                'popup_expires_at'
            ]);
        });
    }
};
