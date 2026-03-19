<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Message Batching: seller ঠিক করবে কতক্ষণ wait করবে
            if (!Schema::hasColumn('clients', 'message_batch_enabled')) {
                $table->boolean('message_batch_enabled')->default(false)
                      ->after('ai_model')
                      ->comment('Enable message batching — wait before processing to join quick multi-part messages');
            }
            if (!Schema::hasColumn('clients', 'message_batch_delay_ms')) {
                $table->integer('message_batch_delay_ms')->default(2000)
                      ->after('message_batch_enabled')
                      ->comment('Milliseconds to wait for more messages before processing (500–5000ms recommended)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumnIfExists('message_batch_enabled');
            $table->dropColumnIfExists('message_batch_delay_ms');
        });
    }
};
