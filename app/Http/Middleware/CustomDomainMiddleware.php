<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;

/**
 * CustomDomainMiddleware
 * 
 * Incoming request এর host check করে,
 * যদি custom domain হয় তাহলে সেই seller এর context set করে।
 * 
 * Route registration: app/Http/Kernel.php এ 'web' group এ add করুন
 * অথবা specific routes এ middleware চাইলে: Route::middleware('custom.domain')
 */
class CustomDomainMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Skip for main app domain and localhost
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($host === $appHost || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return $next($request);
        }

        // Look up custom domain
        $client = Client::where('custom_domain', $host)
            ->where('status', 'active')
            ->first();

        if ($client) {
            // Verify plan allows custom domain
            if (!$client->canAccessFeature('allow_custom_domain')) {
                return response('Custom domain access not allowed.', 403);
            }

            // Set client context for this request
            $request->merge(['_custom_domain_client' => $client]);
            app()->instance('custom_domain_client', $client);

            \Illuminate\Support\Facades\Log::info("🌐 Custom Domain Request: {$host} → Client #{$client->id} ({$client->shop_name})");
        }

        return $next($request);
    }
}
