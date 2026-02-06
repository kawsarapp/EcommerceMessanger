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
    Schema::table('order_sessions', function (Blueprint $table) {
        $table->boolean('is_human_agent_active')->default(false); // true হলে বট রিপ্লাই দিবে না
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_sessions.', function (Blueprint $table) {
            //
        });
    }
};
