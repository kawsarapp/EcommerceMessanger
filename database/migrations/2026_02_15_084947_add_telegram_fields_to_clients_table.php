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
        $table->string('telegram_bot_token')->nullable()->after('fb_page_token');
        $table->string('telegram_chat_id')->nullable()->after('telegram_bot_token');
    });
}

public function down()
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn(['telegram_bot_token', 'telegram_chat_id']);
    });
}

};
