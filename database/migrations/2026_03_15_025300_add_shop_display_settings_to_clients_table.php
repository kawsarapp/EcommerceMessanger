<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Chat & Order Button Controls
            $table->boolean('show_chat_button')->default(true)->after('theme_name');
            $table->boolean('show_order_button')->default(true)->after('show_chat_button');
            
            // Terms & Conditions
            $table->boolean('show_terms_checkbox')->default(false)->after('show_order_button');
            $table->text('terms_conditions_text')->nullable()->after('show_terms_checkbox');
            $table->string('terms_conditions_url')->nullable()->after('terms_conditions_text');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'show_chat_button',
                'show_order_button',
                'show_terms_checkbox',
                'terms_conditions_text',
                'terms_conditions_url',
            ]);
        });
    }
};
