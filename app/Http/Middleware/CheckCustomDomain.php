<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Client;

class CheckCustomDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // ১. যদি মেইন ডোমেইন (asianhost.net) বা লোকালহোস্ট হয়, তবে সোজা ঢুকতে দিবে
        if ($host === $mainDomain || $host === 'www.' . $mainDomain || $host === '127.0.0.1' || $host === 'localhost') {
            return $next($request);
        }

        // ২. যদি কাস্টম ডোমেইন হয়, তবে ডাটাবেসে খুঁজবে
        $client = Client::where('custom_domain', $host)
                        ->orWhere('custom_domain', 'https://' . $host)
                        ->orWhere('custom_domain', 'http://' . $host)
                        ->first();

        // ৩. ড্যাশবোর্ডে ডোমেইনটি না থাকলে ব্লক করে দিবে (Security First!)
        if (!$client) {
            abort(403, 'Unauthorized Domain. This domain is not connected to our system.');
        }

        // ডোমেইন থাকলে রিকোয়েস্ট পাস করবে, সাথে client data পাঠিয়ে দিবে
        $request->merge(['current_client' => $client]);
        return $next($request);
    }
}