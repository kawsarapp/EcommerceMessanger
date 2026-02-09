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
    Schema::table('orders', function (Blueprint $table) {
        if (!Schema::hasColumn('orders', 'customer_email')) {
            $table->string('customer_email')->nullable()->after('customer_phone');
        }
        if (!Schema::hasColumn('orders', 'admin_note')) {
            $table->text('admin_note')->nullable()->after('customer_note');
        }
        if (!Schema::hasColumn('orders', 'payment_method')) {
            $table->string('payment_method')->default('cod')->after('payment_status');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
