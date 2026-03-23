<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Per-channel chat button toggles (seller can turn each on/off independently)
            $table->boolean('show_whatsapp_button')->default(true)->after('widget_greeting');
            $table->boolean('show_messenger_button')->default(true)->after('show_whatsapp_button');
            $table->boolean('show_ai_chat_widget')->default(true)->after('show_messenger_button');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'show_whatsapp_button',
                'show_messenger_button',
                'show_ai_chat_widget',
            ]);
        });
    }
};
