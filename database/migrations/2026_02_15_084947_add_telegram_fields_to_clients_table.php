<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Safety check: column already exist কি না
            if (!Schema::hasColumn('clients', 'telegram_bot_token')) {
                $table->string('telegram_bot_token')->nullable();
            }

            if (!Schema::hasColumn('clients', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'telegram_bot_token') || Schema::hasColumn('clients', 'telegram_chat_id')) {
                $table->dropColumn(['telegram_bot_token', 'telegram_chat_id']);
            }
        });
    }
};