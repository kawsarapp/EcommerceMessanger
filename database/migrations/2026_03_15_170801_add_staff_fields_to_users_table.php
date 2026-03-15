<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // কোন শপের staff সে (client_id)
            $table->unsignedBigInteger('client_id')->nullable()->after('role');
            // Staff এর permissions (JSON array) - যেমন: ['view_orders','edit_orders']
            $table->json('staff_permissions')->nullable()->after('client_id');
            // Staff active কিনা
            $table->boolean('is_active')->default(true)->after('staff_permissions');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'staff_permissions', 'is_active']);
        });
    }
};
