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
        Schema::table('order_sessions', function (Blueprint $table) {
            // ১. পুরনো Global Unique Key টি রিমুভ করা হচ্ছে
            $table->dropUnique('order_sessions_sender_id_unique');

            // ২. নতুন Multi-tenant Unique Key (Shop + Phone) অ্যাড করা হচ্ছে
            $table->unique(['client_id', 'sender_id'], 'client_sender_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_sessions', function (Blueprint $table) {
            // পরিবর্তন রিভার্ট (Revert) করার লজিক
            $table->dropUnique('client_sender_unique');
            $table->unique('sender_id', 'order_sessions_sender_id_unique');
        });
    }
};