<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // SaaS এর জন্য কোন দোকানের প্রোডাক্ট
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); 
            
            // অ্যাডমিনের তৈরি ক্যাটাগরির সাথে রিলেশন
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

            // ১. বেসিক ইনফরমেশন
            $table->string('name');
            $table->string('slug')->unique();
            // $table->string('category'); <-- এই লাইনটি বাদ দেওয়া হয়েছে কারণ আমাদের category_id আছে
            $table->string('sub_category')->nullable();
            $table->string('brand')->nullable();
            $table->text('tags')->nullable(); 

            // ২. প্রাইসিং এবং ডিসকাউন্ট
            $table->decimal('regular_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('discount_type')->nullable(); 
            $table->decimal('tax', 5, 2)->default(0);
            $table->string('currency')->default('BDT');

            // ৩. ডিসক্রিপশন এবং ডিটেইলস
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable(); // আপনি এটি 'description' ও রাখতে পারেন মডালের জন্য
            $table->json('key_features')->nullable(); 

            // ৪. মিডিয়া
            $table->string('thumbnail')->nullable();
            $table->json('gallery')->nullable(); 
            $table->string('video_url')->nullable();

            // ৫. ইনভেন্টরি বা স্টক
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'pre_order'])->default('in_stock');

            // ৬. ভ্যারিয়েশন
            $table->json('colors')->nullable(); 
            $table->json('sizes')->nullable();  
            $table->string('material')->nullable();

            // ৭. রিভিউ এবং রেটিং
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);

            // ৮. শিপিং এবং ডেলিভারি
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('shipping_class')->nullable();

            // ৯. SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // ১০. অন্যান্য
            $table->string('warranty')->nullable();
            $table->text('return_policy')->nullable();
            $table->boolean('is_featured')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};