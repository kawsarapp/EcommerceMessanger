<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('gemini_api_key')->nullable();
            $table->string('openai_api_key')->nullable();
            $table->string('deepseek_api_key')->nullable();
            $table->string('claude_api_key')->nullable();
            $table->string('groq_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'ai_model',
                'gemini_api_key',
                'openai_api_key',
                'deepseek_api_key',
                'claude_api_key',
                'groq_api_key',
            ]);
        });
    }
};
