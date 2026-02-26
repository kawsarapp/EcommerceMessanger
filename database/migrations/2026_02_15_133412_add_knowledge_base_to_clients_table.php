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
        Schema::table('clients', function (Blueprint $table) {
            // Safety check: column already exist কি না
            if (!Schema::hasColumn('clients', 'knowledge_base')) {
                $table->longText('knowledge_base')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'knowledge_base')) {
                $table->dropColumn('knowledge_base');
            }
        });
    }
};