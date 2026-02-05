<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // লজিক: যদি প্রোডাকশন এনভায়রনমেন্ট হয় অথবা URL এর মধ্যে 'ngrok' শব্দটি থাকে
        if ($this->app->environment('production') || str_contains(config('app.url'), 'ngrok')) {
            
            // ১. সব লিংক ও অ্যাসেটকে জোর করে HTTPS করা হবে
            URL::forceScheme('https');
            
            // Laravel 11-এ প্রক্সি সেটিং এখান থেকে করার প্রয়োজন নেই, 
            // URL::forceScheme('https') দিলেই আপনার লগইন এরর ফিক্স হয়ে যাবে।
        }
    }
}