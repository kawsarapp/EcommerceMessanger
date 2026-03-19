<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_sessions', function (Blueprint $table) {
            // Abandoned Cart tracking columns
            if (!Schema::hasColumn('order_sessions', 'reminder_status')) {
                $table->string('reminder_status')->nullable()->default('pending')
                      ->comment('pending | sent | recovered | ignored');
            }
            if (!Schema::hasColumn('order_sessions', 'last_interacted_at')) {
                $table->timestamp('last_interacted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_sessions', function (Blueprint $table) {
            $table->dropColumnIfExists('reminder_status');
            $table->dropColumnIfExists('last_interacted_at');
        });
    }
};
