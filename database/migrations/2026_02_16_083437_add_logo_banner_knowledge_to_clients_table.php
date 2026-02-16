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
    Schema::table('clients', function (Blueprint $table) {
        // ১. লোগো ও ব্যানার (ইমেজ)
        if (!Schema::hasColumn('clients', 'logo')) {
            $table->string('logo')->nullable()->after('shop_name');
        }
        if (!Schema::hasColumn('clients', 'banner')) {
            $table->string('banner')->nullable()->after('logo');
        }

        // ২. AI ফিচার (যদি আগে মিস হয়ে থাকে)
        if (!Schema::hasColumn('clients', 'knowledge_base')) {
            $table->text('knowledge_base')->nullable();
        }
        if (!Schema::hasColumn('clients', 'custom_prompt')) {
            $table->text('custom_prompt')->nullable();
        }
        
        // ৩. অন্য মিসিং ফিল্ড চেক
        if (!Schema::hasColumn('clients', 'announcement_text')) {
            $table->text('announcement_text')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            //
        });
    }
};
