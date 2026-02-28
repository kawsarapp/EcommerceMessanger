<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Instagram Fields
            if (!Schema::hasColumn('clients', 'is_instagram_active')) {
                $table->boolean('is_instagram_active')->default(false)->after('auto_status_update_msg');
            }
            if (!Schema::hasColumn('clients', 'instagram_page_id')) {
                $table->string('instagram_page_id')->nullable()->after('is_instagram_active');
            }
            if (!Schema::hasColumn('clients', 'ig_account_id')) {
                $table->string('ig_account_id')->nullable()->after('instagram_page_id');
            }

            // Telegram Fields
            if (!Schema::hasColumn('clients', 'is_telegram_active')) {
                $table->boolean('is_telegram_active')->default(false)->after('ig_account_id');
            }
            if (!Schema::hasColumn('clients', 'telegram_bot_token')) {
                $table->string('telegram_bot_token')->nullable()->after('is_telegram_active');
            }
            if (!Schema::hasColumn('clients', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('telegram_bot_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columnsToDrop = [
                'is_instagram_active', 
                'instagram_page_id', 
                'ig_account_id', 
                'is_telegram_active', 
                'telegram_bot_token', 
                'telegram_chat_id'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};