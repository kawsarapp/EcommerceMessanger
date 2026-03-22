<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ClientApiRateLimiter
 * 
 * Per-client API rate limiting.
 * Uses the plan's api_rate_limit or admin_permissions override.
 * 
 * Add to routes/api.php: Route::middleware(['auth:sanctum', 'api.rate-limit'])
 */
class ClientApiRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        // SuperAdmin কে rate limit করবো না
        if ($user->isSuperAdmin()) return $next($request);

        $client = Client::where('user_id', $user->id)->first();
        if (!$client) return $next($request);

        // Effective rate limit: admin override > plan > default 60
        $rateLimit = $client->admin_permissions['api_rate_limit']
            ?? $client->seller_settings['api_rate_limit_override']
            ?? $client->plan?->api_rate_limit
            ?? 60;

        $rateLimit = max(10, (int) $rateLimit); // minimum 10 req/min

        // Cache key per client per minute window
        $window    = now()->format('Y-m-d-H-i'); // minute-level window
        $cacheKey  = "api_rate:{$client->id}:{$window}";
        $current   = (int) Cache::get($cacheKey, 0);

        if ($current >= $rateLimit) {
            Log::warning("🚫 API Rate Limit hit | Client: {$client->shop_name} | Limit: {$rateLimit}/min");
            return response()->json([
                'error'       => 'Too Many Requests',
                'message'     => "API rate limit exceeded. Max {$rateLimit} requests/minute.",
                'retry_after' => 60,
            ], 429)->withHeaders([
                'X-RateLimit-Limit'     => $rateLimit,
                'X-RateLimit-Remaining' => 0,
                'Retry-After'           => 60,
            ]);
        }

        Cache::put($cacheKey, $current + 1, 65); // TTL slightly over 60s

        return $next($request)->withHeaders([
            'X-RateLimit-Limit'     => $rateLimit,
            'X-RateLimit-Remaining' => max(0, $rateLimit - $current - 1),
        ]);
    }
}
