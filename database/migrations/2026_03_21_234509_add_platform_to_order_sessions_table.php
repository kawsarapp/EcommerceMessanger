<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_sessions', function (Blueprint $table) {
            // কাস্টমার কোন platform থেকে এসেছে: messenger / whatsapp / instagram / telegram
            $table->string('platform')->default('messenger')->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_sessions', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
