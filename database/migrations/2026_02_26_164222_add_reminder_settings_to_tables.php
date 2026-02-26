<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ১. সেলারের সেটিংস আপডেট
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_reminder_active')->default(false)->after('is_ai_enabled');
            $table->integer('reminder_delay_hours')->default(2)->after('is_reminder_active');
        });

        // ২. সেশনে রিমাইন্ডার স্ট্যাটাস ট্র্যাক করা
        Schema::table('order_sessions', function (Blueprint $table) {
            $table->string('reminder_status')->default('pending')->after('status'); // pending, sent, recovered, ignored
            $table->timestamp('last_interacted_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['is_reminder_active', 'reminder_delay_hours']);
        });

        Schema::table('order_sessions', function (Blueprint $table) {
            $table->dropColumn(['reminder_status', 'last_interacted_at']);
        });
    }
};