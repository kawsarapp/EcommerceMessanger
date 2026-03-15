<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans: AI access control
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_ai')->default(true)->after('allow_api_access');
            $table->json('allowed_ai_models')->nullable()->after('allow_ai'); // null = all, array = specific models
            $table->boolean('allow_own_api_key')->default(false)->after('allowed_ai_models'); // can seller add own key?
        });

        // Clients: Admin-level per-client permission overrides
        Schema::table('clients', function (Blueprint $table) {
            $table->json('admin_permissions')->nullable()->after('widgets');
            // admin_permissions format: { "allow_ai": true, "allow_custom_domain": false, ... }
            // These OVERRIDE plan limits for specific clients
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['allow_ai', 'allowed_ai_models', 'allow_own_api_key']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('admin_permissions');
        });
    }
};
