<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class DomainMappingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Main domain বা localhost হলে skip
        if ($host === $mainDomain || $host === '127.0.0.1' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return $next($request);
        }

        // Custom domain দিয়ে কোনো সক্রিয় shop খোঁজো
        $client = Client::where('custom_domain', $host)
            ->where('status', 'active')
            ->first();

        if (!$client) {
            abort(404, 'Store Not Found');
        }

        // ✅ Plan permission check — custom domain feature আছে কিনা
        if (!$client->canAccessFeature('allow_custom_domain')) {
            abort(403, 'Custom domain access not allowed on your current plan.');
        }

        // Context set করো সারা request এ use এর জন্য
        $request->merge(['current_client' => $client]);
        app()->instance('custom_domain_client', $client);

        Log::info("🌐 Custom Domain: {$host} → Shop #{$client->id} ({$client->shop_name})");

        return $next($request);
    }
}