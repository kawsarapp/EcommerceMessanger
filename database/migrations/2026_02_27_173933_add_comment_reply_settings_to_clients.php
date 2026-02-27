<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('auto_comment_reply')->default(true)->after('custom_prompt');
            $table->boolean('auto_private_reply')->default(true)->after('auto_comment_reply');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['auto_comment_reply', 'auto_private_reply']);
        });
    }
};