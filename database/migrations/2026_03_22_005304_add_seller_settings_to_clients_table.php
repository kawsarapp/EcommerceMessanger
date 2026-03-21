<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            // Seller-level feature on/off toggles (JSON)
            $table->json('seller_settings')->nullable()->after('admin_permissions');
        });
    }
    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('seller_settings');
        });
    }
};
