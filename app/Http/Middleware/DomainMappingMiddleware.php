<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Config;

class DomainMappingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST); // e.g., asianhost.net

        // ১. যদি রিকোয়েস্ট মেইন ডোমেইন বা লোকালহোস্ট থেকে না আসে
        if ($host !== $mainDomain && $host !== '127.0.0.1' && $host !== 'localhost') {
            
            // ২. চেক করি এই ডোমেইনটি কোনো ক্লায়েন্টের কি না
            $client = Client::where('custom_domain', $host)->first();

            if ($client) {
                // ৩. ক্লায়েন্ট পাওয়া গেলে রিকোয়েস্টে ইনজেক্ট করি
                $request->merge(['current_client' => $client]);
                
                // ৪. অপশনাল: স্টোরেজ লিংক ডাইনামিক করা (যদি লাগে)
                // Config::set('app.url', "https://{$host}");
            } else {
                // ডোমেইন ভুল হলে বা ক্লায়েন্ট না থাকলে
                abort(404, 'Store Not Found');
            }
        }

        return $next($request);
    }
}