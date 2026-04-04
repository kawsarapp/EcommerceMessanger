<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'nid_number')) {
                $table->string('nid_number')->nullable()->after('phone');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'facebook_page_link')) {
                $table->string('facebook_page_link')->nullable();
            }
            if (!Schema::hasColumn('clients', 'business_age')) {
                $table->string('business_age')->nullable();
            }
            if (!Schema::hasColumn('clients', 'reference_phone')) {
                $table->string('reference_phone')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'nid_number']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['facebook_page_link', 'business_age', 'reference_phone']);
        });
    }
};
