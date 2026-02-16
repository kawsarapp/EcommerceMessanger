<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // কোন সেলারের পেজ
            $table->string('title'); // e.g. Privacy Policy
            $table->string('slug'); // e.g. privacy-policy
            $table->longText('content')->nullable(); // বিস্তারিত লেখা
            $table->boolean('is_active')->default(true); // পেজ অন/অফ
            $table->timestamps();

            // একই দোকানে যেন একই slug দুইবার না থাকে
            $table->unique(['client_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};