<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class FacebookConnectController extends Controller
{
    // ফিউচার আপডেটের জন্য API ভার্সন কনস্ট্যান্ট
    const GRAPH_API_VERSION = 'v19.0';

    /**
     * স্টেপ ১: ব্যবহারকারীকে ফেসবুকে রিডাইরেক্ট করা
     */
    public function redirect(Request $request)
    {
        $clientId = $request->query('client_id');

        // ১. ভ্যালিডেশন: ক্লায়েন্ট আইডি আছে কিনা
        if (!$clientId) {
            return redirect()->back()->with('error', 'Client ID is missing.');
        }

        // ২. সিকিউরিটি চেক: এই ক্লায়েন্ট আসলে এই ইউজারের কিনা?
        $client = Client::find($clientId);
        if (!$client || ($client->user_id !== auth()->id() && auth()->id() !== 1)) {
            return redirect()->back()->with('error', 'Unauthorized access to this shop.');
        }
        
        // ৩. সেশনে আইডি রাখা
        session(['connect_client_id' => $clientId]);

        // ৪. ফেসবুকে পাঠানো
        return Socialite::driver('facebook')
            ->scopes([
                'pages_show_list',      // পেজ লিস্ট দেখার জন্য
                'pages_read_engagement', // পেজের কন্টেন্ট পড়ার জন্য
                'pages_manage_metadata', // ওয়েব্হুক সাবস্ক্রাইব করার জন্য
                'pages_messaging'       // মেসেজ রিপ্লাই করার জন্য
            ])
            ->redirect();
    }

    /**
     * স্টেপ ২: ফেসবুক থেকে ফিরে আসার পর হ্যান্ডেল করা
     */
    public function callback()
    {
        try {
            // ১. সেশন এবং ইউজার চেক
            $clientId = session('connect_client_id');
            if (!$clientId) {
                throw new Exception('Session expired or Client ID missing.');
            }

            // ২. Socialite ইউজার ডাটা (Stateless ব্যবহার করা নিরাপদ যদি সেশন এরর দেয়)
            $fbUser = Socialite::driver('facebook')->user();
            
            // ৩. ক্লায়েন্ট ভেরিফিকেশন
            $client = Client::findOrFail($clientId);

            // ৪. পেজ লিস্ট আনা (Helper Function ব্যবহার করা হয়েছে)
            $pages = $this->getFacebookPages($fbUser->token);

            if (empty($pages)) {
                return redirect("/admin/clients/{$clientId}/edit")
                    ->with('error', 'No Facebook Pages found directly manageable by this account.');
            }

            // ৫. প্রথম পেজটি সিলেক্ট করা (Logic: SaaS-এর জন্য অটোমেশন)
            $targetPage = $pages[0];

            // ৬. লং-লিভড টোকেন জেনারেট (Token Exchange)
            $finalToken = $this->getLongLivedToken($targetPage['access_token']);

            // ৭. ওয়েব্হুক সাবস্ক্রাইব করা (Webhook Registration)
            $isSubscribed = $this->subscribeToWebhooks($targetPage['id'], $finalToken);

            // ৮. ডাটাবেস আপডেট (Transaction ব্যবহার করা হয়েছে ডাটা সেফটির জন্য)
            DB::transaction(function () use ($client, $targetPage, $finalToken, $isSubscribed) {
                $client->update([
                    'fb_page_id'          => $targetPage['id'],
                    'fb_page_token'       => $finalToken,
                    'shop_name'           => $targetPage['name'], // শপ নেম আপডেট (অপশনাল)
                    'status'              => 'active',
                    'webhook_verified_at' => $isSubscribed ? now() : null,
                ]);
            });

            return redirect("/admin/clients/{$clientId}/edit")
                ->with('success', "Facebook Page '{$targetPage['name']}' Connected Successfully! 🚀");

        } catch (Exception $e) {
            // বিস্তারিত লগিং (ডিবাগিং এর জন্য)
            Log::error('FB Connect Critical Error:', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect('/admin')->with('error', 'Connection Failed: ' . $e->getMessage());
        }
    }

    // --- Private Helper Functions (Clean Code) ---

    /**
     * ইউজারের সব পেজ ফেচ করা
     */
    private function getFacebookPages($userAccessToken)
    {
        $response = Http::get("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/me/accounts", [
            'access_token' => $userAccessToken,
            'fields'       => 'name,access_token,id,tasks', // অপটিমাইজড: শুধু প্রয়োজনীয় ফিল্ড
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch pages: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * শর্ট-লিভড টোকেনকে লং-লিভড টোকেনে কনভার্ট করা (৬০ দিনের জন্য)
     */
    private function getLongLivedToken($shortLivedToken)
    {
        $response = Http::get("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.client_id'),
            'client_secret'     => config('services.facebook.client_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }
        
        // ফেইল করলে আগের টোকেনই রিটার্ন করি (Fallback)
        Log::warning('Failed to exchange long-lived token. Using default.');
        return $shortLivedToken;
    }

    /**
     * ওয়েব্হুক সাবস্ক্রাইব করা (Magic Step)
     */
    private function subscribeToWebhooks($pageId, $accessToken)
    {
        $response = Http::post("https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$pageId}/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks',
            'access_token'      => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error("Webhook Subscription Failed for Page {$pageId}: " . $response->body());
            return false;
        }

        return true;
    }
}