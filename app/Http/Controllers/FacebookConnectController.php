<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class FacebookConnectController extends Controller
{
    const GRAPH_API_VERSION = 'v19.0';

    /**
     * স্টেপ ১: ব্যবহারকারীকে ফেসবুকে রিডাইরেক্ট করা
     */
    public function redirect(Request $request)
    {
        $clientId = $request->query('client_id');

        // ১. ভ্যালিডেশন
        if (!$clientId) {
            return redirect()->back()->with('error', 'Client ID is missing.');
        }

        // ২. সিকিউরিটি চেক
        $client = Client::find($clientId);
        if (!$client || ($client->user_id !== auth()->id() && auth()->id() !== 1)) {
            return redirect()->back()->with('error', 'Unauthorized access to this shop.');
        }
        
        // ৩. সেশনে আইডি রাখা
        session(['connect_client_id' => $clientId]);

        $appId = env('FACEBOOK_APP_ID');
        $redirectUri = route('auth.facebook.callback');
        $scopes = 'pages_show_list,pages_messaging,pages_read_engagement,pages_manage_metadata';
        
        $loginUrl = "https://www.facebook.com/".self::GRAPH_API_VERSION."/dialog/oauth?client_id={$appId}&redirect_uri={$redirectUri}&scope={$scopes}&response_type=code";

        // ৪. ফেসবুকে পাঠানো
        return redirect()->away($loginUrl);
    }

    /**
     * স্টেপ ২: ফেসবুক থেকে ফিরে আসার পর হ্যান্ডেল করা
     */
    public function callback(Request $request)
    {
        $clientId = session('connect_client_id');

        try {
            $code = $request->query('code');

            if (!$code || !$clientId) {
                throw new Exception('Authentication cancelled or session expired.');
            }

            $client = Client::findOrFail($clientId);

            // ১. Code দিয়ে User Access Token জানা
            $tokenResponse = Http::get("https://graph.facebook.com/".self::GRAPH_API_VERSION."/oauth/access_token", [
                'client_id' => env('FACEBOOK_APP_ID'),
                'client_secret' => env('FACEBOOK_APP_SECRET'),
                'redirect_uri' => route('auth.facebook.callback'),
                'code' => $code,
            ])->json();

            if (isset($tokenResponse['error'])) {
                throw new Exception($tokenResponse['error']['message']);
            }

            $userAccessToken = $tokenResponse['access_token'];

            // ২. পেজ লিস্ট আনা
            $pages = $this->getFacebookPages($userAccessToken);

            if (empty($pages)) {
                return redirect("/admin/clients/{$clientId}/edit")
                    ->with('error', 'No Facebook Pages found directly manageable by this account.');
            }

            // ৩. প্রথম পেজটি সিলেক্ট করা (Logic: SaaS-এর জন্য অটোমেশন)
            $targetPage = $pages[0];

            // ৪. লং-লিভড টোকেন জেনারেট (Token Exchange)
            $finalToken = $this->getLongLivedToken($targetPage['access_token']);

            // ৫. ওয়েব্হুক সাবস্ক্রাইব করা (Webhook Registration)
            $isSubscribed = $this->subscribeToWebhooks($targetPage['id'], $finalToken);

            // ৬. ডাটাবেস আপডেট
            DB::transaction(function () use ($client, $targetPage, $finalToken, $isSubscribed) {
                $client->update([
                    'fb_page_id'          => $targetPage['id'],
                    'fb_page_token'       => $finalToken,
                    'status'              => 'active',
                    'webhook_verified_at' => $isSubscribed ? now() : null,
                ]);
            });

            return redirect("/admin/clients/{$clientId}/edit")
                ->with('success', "Facebook Page '{$targetPage['name']}' Connected Successfully! 🚀");

        } catch (Exception $e) {
            Log::error('FB Connect API Error:', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $redirectUrl = $clientId ? "/admin/clients/{$clientId}/edit" : "/admin";
            return redirect($redirectUrl)->with('error', 'Connection Failed: ' . $e->getMessage());
        }
    }

    // --- Private Helper Functions ---

    private function getFacebookPages($userAccessToken)
    {
        $response = Http::get("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/me/accounts", [
            'access_token' => $userAccessToken,
            'fields'       => 'name,access_token,id,tasks',
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch pages: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    private function getLongLivedToken($shortLivedToken)
    {
        // Get App ID and Secret from environment
        $appId = env('FACEBOOK_APP_ID');
        $appSecret = env('FACEBOOK_APP_SECRET');

        if (!$appId || !$appSecret) {
            Log::warning('FACEBOOK_APP_ID or FACEBOOK_APP_SECRET is not set. Cannot exchange token.');
            return $shortLivedToken;
        }

        $response = Http::get("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->successful() && isset($response->json()['access_token'])) {
            return $response->json()['access_token'];
        }
        
        Log::warning('Failed to exchange long-lived token. Using default short-lived token.');
        return $shortLivedToken;
    }

    private function subscribeToWebhooks($pageId, $accessToken)
    {
        $response = Http::post("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$pageId}/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks,messaging_optins',
            'access_token'      => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error("Webhook Subscription Failed for Page {$pageId}: " . $response->body());
            return false;
        }

        return true;
    }
}