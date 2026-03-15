<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'admin_note')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('admin_note')->nullable()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'admin_note')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('admin_note');
            });
        }
    }
};
