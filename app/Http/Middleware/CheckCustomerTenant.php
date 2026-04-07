<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Shop\ShopClientService;

class CheckCustomerTenant
{
    protected $clientService;

    public function __construct(ShopClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('customer')->check()) {
            // Attempt to resolve the current shop/client context
            $slug = $request->route('slug') ?? null;
            $client = $this->clientService->getSafeClient($request, $slug);

            if ($client && $client->exists) {
                // Security Check: Does the authenticated customer belong to this client?
                if (Auth::guard('customer')->user()->client_id !== $client->id) {
                    
                    // Cross-tenant leak detected. Destroy the session instantly.
                    Auth::guard('customer')->logout();
                    
                    // Regenerate session to prevent fixation
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // Allow the request to continue as a GUEST user.
                }
            }
        }

        return $next($request);
    }
}
