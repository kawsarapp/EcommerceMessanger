<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // কলামগুলো ডাটাবেসে আগে থেকে না থাকলে তবেই তৈরি করবে
            if (!Schema::hasColumn('clients', 'is_whatsapp_active')) {
                $table->boolean('is_whatsapp_active')->default(false);
            }
            if (!Schema::hasColumn('clients', 'whatsapp_type')) {
                $table->string('whatsapp_type')->nullable(); // 'unofficial' or 'official'
            }
            if (!Schema::hasColumn('clients', 'wa_instance_id')) {
                $table->string('wa_instance_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'wa_status')) {
                $table->string('wa_status')->default('disconnected');
            }
            if (!Schema::hasColumn('clients', 'wa_phone_number_id')) {
                $table->string('wa_phone_number_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'wa_access_token')) {
                $table->text('wa_access_token')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'is_whatsapp_active', 'whatsapp_type', 
                'wa_instance_id', 'wa_status', 
                'wa_phone_number_id', 'wa_access_token'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};