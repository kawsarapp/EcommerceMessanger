<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * bKash Tokenized Checkout PGW Service
 *
 * API Reference: https://developer.bkash.com/reference/create-payment
 * Version: v1.2.0-beta (Tokenized Checkout)
 *
 * Flow:
 *  1. getToken()       → access_token (cached 55 min)
 *  2. createPayment()  → bkashURL (redirect customer)
 *  3. executePayment() → finalize after callback
 *  4. queryPayment()   → security double-check
 */
class BkashPgwService
{
    // ─── API Base URLs ────────────────────────────────────────────
    const SANDBOX_BASE = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';
    const LIVE_BASE    = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

    // Token cache TTL: 55 minutes (bKash token valid for 60 min)
    const TOKEN_TTL_SECONDS = 55 * 60;

    // ─── Helpers ──────────────────────────────────────────────────

    protected function baseUrl(array $config): string
    {
        return ($config['is_sandbox'] ?? true) ? self::SANDBOX_BASE : self::LIVE_BASE;
    }

    /**
     * Unique cache key per seller (by their app_key)
     */
    protected function tokenCacheKey(array $config): string
    {
        return 'bkash_pgw_token_' . md5($config['app_key'] ?? '');
    }

    // ─── 1. Grant Token ──────────────────────────────────────────

    /**
     * Get bKash access token. Cached for 55 minutes.
     * Returns the token string or throws an exception.
     */
    public function getToken(array $config): string
    {
        $cacheKey = $this->tokenCacheKey($config);

        // Return cached token if available
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'username'      => $config['username'],
            'password'      => $config['password'],
        ])->timeout(15)->post($this->baseUrl($config) . '/token/grant', [
            'app_key'    => $config['app_key'],
            'app_secret' => $config['app_secret'],
        ]);

        $data = $response->json();

        if (empty($data['id_token'])) {
            Log::error('bKash PGW: Token grant failed', $data);
            throw new \RuntimeException('bKash authentication failed: ' . ($data['statusMessage'] ?? 'Unknown error'));
        }

        // Cache token
        Cache::put($cacheKey, $data['id_token'], self::TOKEN_TTL_SECONDS);

        return $data['id_token'];
    }

    /**
     * Invalidate cached token (call on any 401 response)
     */
    public function invalidateToken(array $config): void
    {
        Cache::forget($this->tokenCacheKey($config));
    }

    // ─── 2. Create Payment ───────────────────────────────────────

    /**
     * Create a bKash payment.
     * Returns ['bkashURL' => '...', 'paymentID' => '...'] or throws.
     *
     * @param  array  $config  Seller's bKash PGW config
     * @param  Order  $order   The order to pay for
     * @param  string $callbackUrl  Our execute URL (bKash will redirect here)
     * @param  string $cancelUrl    Our cancel URL
     */
    public function createPayment(array $config, Order $order, string $callbackUrl, string $cancelUrl): array
    {
        $token  = $this->getToken($config);
        $amount = $this->resolveAmount($order);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'X-APP-Key'     => $config['app_key'],
        ])->timeout(15)->post($this->baseUrl($config) . '/create', [
            'mode'                  => '0011',          // Tokenized Checkout
            'payerReference'        => (string) $order->id,
            'callbackURL'           => $callbackUrl,
            'cancelledCallbackURL'  => $cancelUrl,
            'amount'                => number_format((float) $amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => 'INV-' . $order->id . '-' . time(),
        ]);

        $data = $response->json();

        if (($data['statusCode'] ?? '') !== '0000' || empty($data['bkashURL'])) {
            // Token expired? invalidate and rethrow
            if (($data['statusCode'] ?? '') === '2001') {
                $this->invalidateToken($config);
            }
            Log::error('bKash PGW: Create payment failed', ['order' => $order->id, 'response' => $data]);
            throw new \RuntimeException('bKash payment creation failed: ' . ($data['statusMessage'] ?? 'Unknown error'));
        }

        return [
            'bkashURL'  => $data['bkashURL'],
            'paymentID' => $data['paymentID'],
        ];
    }

    // ─── 3. Execute Payment ──────────────────────────────────────

    /**
     * Execute (finalize) a bKash payment after customer pays.
     * Returns the full API response array.
     */
    public function executePayment(array $config, string $paymentID): array
    {
        $token = $this->getToken($config);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'X-APP-Key'     => $config['app_key'],
        ])->timeout(15)->post($this->baseUrl($config) . '/execute', [
            'paymentID' => $paymentID,
        ]);

        $data = $response->json();

        if (($data['statusCode'] ?? '') !== '0000') {
            if (($data['statusCode'] ?? '') === '2001') {
                $this->invalidateToken($config);
            }
            Log::warning('bKash PGW: Execute failed', ['paymentID' => $paymentID, 'response' => $data]);
            throw new \RuntimeException('bKash payment execution failed: ' . ($data['statusMessage'] ?? 'Unknown error'));
        }

        return $data;
    }

    // ─── 4. Query Payment (Security) ─────────────────────────────

    /**
     * Query payment status for final verification.
     * Use after executePayment() to confirm transactionStatus = 'Completed'.
     */
    public function queryPayment(array $config, string $paymentID): array
    {
        $token = $this->getToken($config);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'X-APP-Key'     => $config['app_key'],
        ])->timeout(15)->get($this->baseUrl($config) . '/payment/status', [
            'paymentID' => $paymentID,
        ]);

        return $response->json();
    }

    // ─── Security Helpers ─────────────────────────────────────────

    /**
     * Order এর actual payable amount বের করো
     * (advance থাকলে advance, না থাকলে total)
     */
    public function resolveAmount(Order $order): float
    {
        $advance = (float) ($order->advance_amount ?? 0);
        return $advance > 0 ? $advance : (float) $order->total_amount;
    }

    /**
     * Execute response থেকে amount verify করো
     * Order amount এর সাথে bKash amount মেলে কিনা দেখো
     *
     * @throws \RuntimeException if amounts don't match
     */
    public function verifyAmount(Order $order, array $executeData): void
    {
        $expectedAmount = $this->resolveAmount($order);
        $paidAmount     = (float) ($executeData['amount'] ?? 0);

        // 1 টাকা tolerance রাখা হয়েছে রাউন্ডিং এর জন্য
        if ($paidAmount < ($expectedAmount - 1)) {
            Log::warning('bKash PGW: Amount mismatch', [
                'order_id' => $order->id,
                'expected' => $expectedAmount,
                'paid'     => $paidAmount,
            ]);
            throw new \RuntimeException("Payment amount mismatch. Expected ৳{$expectedAmount}, got ৳{$paidAmount}.");
        }
    }

    /**
     * Payment টা সত্যিই Completed কিনা query করে verify করো
     *
     * @throws \RuntimeException if not completed
     */
    public function verifyCompletion(array $config, string $paymentID): array
    {
        $status = $this->queryPayment($config, $paymentID);

        if (($status['transactionStatus'] ?? '') !== 'Completed') {
            Log::warning('bKash PGW: Transaction not completed', [
                'paymentID' => $paymentID,
                'status'    => $status,
            ]);
            throw new \RuntimeException('bKash payment is not in Completed state. Status: ' . ($status['transactionStatus'] ?? 'unknown'));
        }

        return $status;
    }
}
