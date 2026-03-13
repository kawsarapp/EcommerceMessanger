<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    /**
     * Redirect User to Facebook Login Page
     */
    public function redirect(Request $request)
    {
        $clientId = $request->query('client_id');
        if (!$clientId) {
            return redirect()->back()->with('error', 'Client ID is missing.');
        }

        // Save Client ID in session to use it after callback
        session(['setup_fb_client_id' => $clientId]);

        $appId = env('FACEBOOK_APP_ID');
        $redirectUri = route('auth.facebook.callback');
        // Scopes needed for Page Messaging & Read
        $scopes = 'pages_show_list,pages_messaging,pages_read_engagement,pages_manage_metadata';
        
        $loginUrl = "https://www.facebook.com/v19.0/dialog/oauth?client_id={$appId}&redirect_uri={$redirectUri}&scope={$scopes}&response_type=code";

        return redirect()->away($loginUrl);
    }

    /**
     * Handle Callback from Facebook
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $clientId = session('setup_fb_client_id');

        if (!$code || !$clientId) {
            return redirect('/admin/clients')->with('error', 'Authentication failed or cancelled.');
        }

        try {
            // 1. Exchange Code for User Access Token
            $tokenResponse = Http::get("https://graph.facebook.com/v19.0/oauth/access_token", [
                'client_id' => env('FACEBOOK_APP_ID'),
                'client_secret' => env('FACEBOOK_APP_SECRET'),
                'redirect_uri' => route('auth.facebook.callback'),
                'code' => $code,
            ])->json();

            if (isset($tokenResponse['error'])) {
                throw new \Exception($tokenResponse['error']['message']);
            }

            $userAccessToken = $tokenResponse['access_token'];

            // 2. We could optionally get long-lived token here if needed
            // ...

            // 3. Temporarily save this short-lived token to session, 
            // so we can show a page selection form if the user has multiple pages,
            // or we can auto-fetch the first page and save it. Let's auto-fetch for simplicity.

            $pagesResponse = Http::get("https://graph.facebook.com/v19.0/me/accounts", [
                'access_token' => $userAccessToken,
            ])->json();

            if (isset($pagesResponse['error'])) {
                throw new \Exception($pagesResponse['error']['message']);
            }

            $pages = $pagesResponse['data'] ?? [];
            if (count($pages) === 0) {
                return redirect('/admin/clients/'.$clientId.'/edit')->with('error', 'No Facebook Pages found for this account.');
            }

            // By default, let's connect the very first page they manage
            $firstPage = $pages[0];
            $pageId = $firstPage['id'];
            $pageAccessToken = $firstPage['access_token'];
            $pageName = $firstPage['name'];

            // 4. Save to Client DB
            $client = Client::find($clientId);
            if ($client) {
                $client->update([
                    'fb_page_id' => $pageId,
                    'fb_page_token' => $pageAccessToken,
                    // Note: System Webhook token is manually set, so no need to alter fb_verify_token
                ]);

                // 5. Try to Subcribe this Page to our Webhook automatically
                // This requires the app to be set up properly in Developer Portal
                Http::post("https://graph.facebook.com/v19.0/{$pageId}/subscribed_apps", [
                    'subscribed_fields' => 'messages,messaging_postbacks,messaging_optins',
                    'access_token' => $pageAccessToken,
                ]);

                Log::info("Facebook Auto-Connect Success: Client #{$clientId} connected Page {$pageName} ({$pageId})");

                return redirect('/admin/clients/'.$clientId.'/edit')
                    ->with('success', "Facebook Page '{$pageName}' Connected Successfully!");
            }

            return redirect('/admin/clients')->with('error', 'Client not found in system.');

        } catch (\Exception $e) {
            Log::error("FB Auto-login Error: " . $e->getMessage());
            return redirect('/admin/clients/'.$clientId.'/edit')->with('error', 'Facebook connection failed: ' . $e->getMessage());
        }
    }
}
