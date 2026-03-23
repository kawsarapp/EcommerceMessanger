<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Widget display name (shown in chatbot header)
            $table->string('widget_name')->nullable()->after('shop_name');
            // Comma-separated allowed domains: "example.com,shop.example.com"
            // Empty = allow all (less secure but easier to start)
            $table->text('widget_allowed_domains')->nullable()->after('widget_name');
            // Position: bottom-right (default) or bottom-left
            $table->string('widget_position', 20)->default('bottom-right')->after('widget_allowed_domains');
            // Greeting message override
            $table->text('widget_greeting')->nullable()->after('widget_position');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'widget_name',
                'widget_allowed_domains',
                'widget_position',
                'widget_greeting',
            ]);
        });
    }
};
