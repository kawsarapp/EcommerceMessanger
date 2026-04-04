<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use App\Services\BkashPgwService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🔒 SECURITY HELPER — Order ownership verify করো
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    /**
     * Order টি এই client এর কিনা verify করো।
     * এটা ছাড়া attacker অন্যের order_id দিয়ে payment status পরিবর্তন করতে পারত।
     */
    protected function verifyOrderOwnership(Order $order, Client $client): bool
    {
        return $order->client_id === $client->id;
    }

    /**
     * Request থেকে current_client বের করো (DomainMappingMiddleware inject করে)
     */
    protected function getClientFromRequest(Request $request): ?Client
    {
        return $request->attributes->get('current_client');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📲 bKASH PERSONAL — Manual Reference System
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function confirmBkashPersonal(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'trx_id'   => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'amount'   => 'required|numeric|min:1|max:999999',
        ]);

        $order  = Order::findOrFail($request->order_id);
        $client = $order->client;

        // 🔒 Gateway active আছে কিনা
        if (!$client->isPaymentGatewayActive('bkash_personal')) {
            return response()->json(['success' => false, 'message' => 'bKash payment is not enabled for this shop.'], 403);
        }

        // 🔒 Order already paid হলে আর TrxID নেওয়া যাবে না
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'This order is already marked as paid.'], 422);
        }

        // 🔒 TrxID duplicate check — same shop এ already same TrxID আছে কিনা
        $duplicate = Order::where('client_id', $client->id)
            ->where('payment_reference', $request->trx_id)
            ->where('id', '!=', $order->id)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'This Transaction ID has already been used. Please check your TrxID.'], 422);
        }

        $order->update([
            'payment_method'    => 'bkash_personal',
            'payment_status'    => 'pending', // Seller manually verify করবে
            'payment_reference' => $request->trx_id,
            'advance_amount'    => min((float) $request->amount, (float) $order->total_amount),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'আপনার bKash Transaction ID সফলভাবে save হয়েছে। Seller verify করলে আপনাকে জানানো হবে।',
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📱 bKASH MERCHANT — Manual Reference
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function confirmBkashMerchant(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'trx_id'   => ['required', 'string', 'min:5', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'amount'   => 'required|numeric|min:1|max:999999',
        ]);

        $order  = Order::findOrFail($request->order_id);
        $client = $order->client;

        // 🔒 Gateway active check
        if (!$client->isPaymentGatewayActive('bkash_merchant')) {
            return response()->json(['success' => false, 'message' => 'bKash Merchant payment is not enabled.'], 403);
        }

        // 🔒 Already paid check
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'This order is already marked as paid.'], 422);
        }

        // 🔒 Duplicate TrxID check
        $duplicate = Order::where('client_id', $client->id)
            ->where('payment_reference', $request->trx_id)
            ->where('id', '!=', $order->id)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'This Transaction ID has already been used.'], 422);
        }

        $order->update([
            'payment_method'    => 'bkash_merchant',
            'payment_status'    => 'pending',
            'payment_reference' => $request->trx_id,
            'advance_amount'    => min((float) $request->amount, (float) $order->total_amount),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'bKash Transaction ID সফলভাবে save হয়েছে!',
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 💳 SSL COMMERZ
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function initiateSslCommerz(Request $request, $orderId)
    {
        $order  = Order::with('client')->findOrFail($orderId);
        $client = $order->client;

        // 🔒 Gateway active check
        if (!$client->isPaymentGatewayActive('sslcommerz')) {
            abort(403, 'SSL Commerz is not enabled for this shop.');
        }

        // 🔒 Already paid হলে re-initiate করা যাবে না
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'This order is already paid.');
        }

        $config   = $client->getPaymentGatewayConfig('sslcommerz');
        $isLive   = $config['is_live'] ?? false;
        $baseUrl  = $isLive
            ? 'https://securepay.sslcommerz.com/gwprocess/v3/api.php'
            : 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';

        // Amount: partial or full
        $amount = ($order->advance_amount ?? 0) > 0
            ? $order->advance_amount
            : $order->total_amount;

        $tranId       = 'TXN_' . $orderId . '_' . time();
        $callbackBase = url("/payment/sslcommerz/{$orderId}");

        $postData = [
            'store_id'         => $config['store_id'],
            'store_passwd'     => $config['store_password'],
            'total_amount'     => number_format((float) $amount, 2, '.', ''),
            'currency'         => 'BDT',
            'tran_id'          => $tranId,
            'success_url'      => $callbackBase . '/success',
            'fail_url'         => $callbackBase . '/fail',
            'cancel_url'       => $callbackBase . '/cancel',
            'cus_name'         => $order->customer_name ?? 'Customer',
            'cus_email'        => $order->customer_email ?? 'noreply@shop.com',
            'cus_phone'        => $order->customer_phone ?? '01700000000',
            'cus_add1'         => $order->customer_address ?? 'Bangladesh',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'shipping_method'  => 'NO',
            'product_name'     => 'Order #' . $orderId,
            'product_category' => 'General',
            'product_profile'  => 'general',
        ];

        try {
            $response = Http::asForm()->timeout(15)->post($baseUrl, $postData);
            $data     = $response->json();

            if (isset($data['GatewayPageURL'])) {
                // TrxID store করি verify এর জন্য
                $order->update([
                    'payment_method'    => 'sslcommerz',
                    'payment_status'    => 'pending',
                    'payment_reference' => $tranId,
                ]);
                return redirect($data['GatewayPageURL']);
            }

            Log::warning('SSLCommerz Init: No GatewayPageURL', $data);
            return back()->with('error', 'SSL Commerz connection failed: ' . ($data['failedreason'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('SSLCommerz Init Error: ' . $e->getMessage());
            return back()->with('error', 'Payment gateway error. Please try again.');
        }
    }

    public function sslcommerzSuccess(Request $request, $orderId)
    {
        $order  = Order::findOrFail($orderId);
        $client = $order->client;

        // 🔒 Already paid হলে re-process করা যাবে না
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'Your order is already confirmed.');
        }

        // 🔒 val_id must be present
        if (empty($request->val_id)) {
            Log::warning("SSLCommerz success callback missing val_id for order #{$orderId}");
            return $this->redirectToOrderFail($order, $client, 'Invalid payment callback received.');
        }

        $config        = $client->getPaymentGatewayConfig('sslcommerz');
        $isLive        = $config['is_live'] ?? false;
        $validationUrl = $isLive
            ? 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php'
            : 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';

        try {
            $response = Http::timeout(15)->get($validationUrl, [
                'val_id'       => $request->val_id,
                'store_id'     => $config['store_id'],
                'store_passwd' => $config['store_password'],
                'format'       => 'json',
            ]);

            $data = $response->json();

            if (in_array($data['status'] ?? '', ['VALID', 'VALIDATED'])) {

                // 🔒 TrxID match check — আমাদের stored TrxID এর সাথে কি SSL এর respond মিলছে?
                $storedRef  = $order->payment_reference ?? '';
                $returnedId = $data['tran_id'] ?? '';

                if ($storedRef && $returnedId && $storedRef !== $returnedId) {
                    Log::warning("SSLCommerz TrxID mismatch for order #{$orderId}: stored={$storedRef}, returned={$returnedId}");
                    return $this->redirectToOrderFail($order, $client, 'Payment reference mismatch. Please contact support.');
                }

                // 🔒 Amount mismatch check — SSL এর amount কি order amount মেলে?
                $paidAmount  = (float) ($data['amount'] ?? 0);
                $orderAmount = (float) (($order->advance_amount ?? 0) > 0 ? $order->advance_amount : $order->total_amount);

                if ($paidAmount < ($orderAmount - 1)) { // ১ টাকা tolerance
                    Log::warning("SSLCommerz amount mismatch for order #{$orderId}: expected={$orderAmount}, paid={$paidAmount}");
                    return $this->redirectToOrderFail($order, $client, 'Payment amount mismatch. Please contact support.');
                }

                $order->update([
                    'payment_status'    => 'paid',
                    'payment_reference' => $data['bank_tran_id'] ?? $data['tran_id'] ?? $storedRef,
                    'advance_amount'    => $paidAmount,
                ]);

                return $this->redirectToOrderSuccess($order, $client, 'Payment successful! Your order has been confirmed.');
            }

            Log::warning("SSLCommerz invalid status for order #{$orderId}: " . ($data['status'] ?? 'null'));

        } catch (\Exception $e) {
            Log::error('SSLCommerz Validation Error: ' . $e->getMessage());
        }

        return $this->redirectToOrderFail($order, $client, 'Payment validation failed. Please contact support.');
    }

    public function sslcommerzFail(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'failed']);
        }

        return $this->redirectToOrderFail($order, $order->client, 'Payment failed. Please try again or choose another method.');
    }

    public function sslcommerzCancel(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'pending']);
        }

        return $this->redirectToOrderFail($order, $order->client, 'Payment cancelled. You can try again.');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🌙 SURJOPAY
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function initiateSurjopay(Request $request, $orderId)
    {
        $order  = Order::with('client')->findOrFail($orderId);
        $client = $order->client;

        // 🔒 Gateway active check
        if (!$client->isPaymentGatewayActive('surjopay')) {
            abort(403, 'Surjopay is not enabled for this shop.');
        }

        // 🔒 Already paid
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'This order is already paid.');
        }

        $config   = $client->getPaymentGatewayConfig('surjopay');
        $isLive   = $config['is_live'] ?? false;
        $authUrl  = $isLive ? 'https://engine.surjopay.com.bd/api/get_token'    : 'https://sandbox.surjopay.com.bd/api/get_token';
        $orderUrl = $isLive ? 'https://engine.surjopay.com.bd/api/place-order'  : 'https://sandbox.surjopay.com.bd/api/place-order';

        $amount = ($order->advance_amount ?? 0) > 0
            ? $order->advance_amount
            : $order->total_amount;

        $spOrderId = 'SP_' . $orderId . '_' . time();

        try {
            $tokenResponse = Http::timeout(10)->post($authUrl, [
                'username' => $config['username'],
                'password' => $config['password'],
            ])->json();

            if (empty($tokenResponse['token'])) {
                Log::warning("Surjopay token failed for order #{$orderId}", $tokenResponse);
                return back()->with('error', 'Surjopay authentication failed. Please try again.');
            }

            $callbackBase  = url("/payment/surjopay/{$orderId}");
            $orderResponse = Http::withToken($tokenResponse['token'])->timeout(10)->post($orderUrl, [
                'order_id'           => $spOrderId,
                'currency'           => 'BDT',
                'amount'             => number_format((float) $amount, 2, '.', ''),
                'discount_amount'    => 0,
                'disc_percent'       => 0,
                'customer_name'      => $order->customer_name ?? 'Customer',
                'customer_phone'     => $order->customer_phone ?? '01700000000',
                'customer_email'     => $order->customer_email ?? 'noreply@shop.com',
                'customer_address'   => $order->customer_address ?? 'Bangladesh',
                'customer_city'      => 'Dhaka',
                'customer_post_code' => '1200',
                'client_ip'          => $request->ip(),
                'success_url'        => $callbackBase . '/success',
                'fail_url'           => $callbackBase . '/fail',
                'cancel_url'         => $callbackBase . '/cancel',
            ])->json();

            if (!empty($orderResponse['checkout_url'])) {
                // Store our generated order_id for verification later
                $order->update([
                    'payment_method'    => 'surjopay',
                    'payment_status'    => 'pending',
                    'payment_reference' => $spOrderId,
                ]);

                return redirect($orderResponse['checkout_url']);
            }

            Log::warning("Surjopay no checkout_url for order #{$orderId}", $orderResponse);
            return back()->with('error', 'Surjopay order creation failed. Please try again.');

        } catch (\Exception $e) {
            Log::error('Surjopay Init Error: ' . $e->getMessage());
            return back()->with('error', 'Payment gateway error. Please try again.');
        }
    }

    public function surjopaySuccess(Request $request, $orderId)
    {
        $order  = Order::findOrFail($orderId);
        $client = $order->client;

        // 🔒 Already paid
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'Your order is already confirmed.');
        }

        $config  = $client->getPaymentGatewayConfig('surjopay');
        $isLive  = $config['is_live'] ?? false;
        $authUrl = $isLive ? 'https://engine.surjopay.com.bd/api/get_token'    : 'https://sandbox.surjopay.com.bd/api/get_token';
        $verifyUrl = $isLive ? 'https://engine.surjopay.com.bd/api/verification' : 'https://sandbox.surjopay.com.bd/api/verification';

        // 🔒 Stored reference verify করা হবে
        $storedRef = $order->payment_reference;

        try {
            $tokenResponse = Http::timeout(10)->post($authUrl, [
                'username' => $config['username'],
                'password' => $config['password'],
            ])->json();

            if (empty($tokenResponse['token'])) {
                Log::warning("Surjopay verify token failed for order #{$orderId}");
                return $this->redirectToOrderFail($order, $client, 'Payment verification failed. Please contact support.');
            }

            $spOrderId    = $request->order_id ?? $storedRef;
            $verification = Http::withToken($tokenResponse['token'])->timeout(10)
                ->post($verifyUrl, ['order_id' => $spOrderId])
                ->json();

            if (!empty($verification) && isset($verification[0])) {
                $result = $verification[0];

                // 🔒 bank_status check
                if (($result['bank_status'] ?? '') === 'Success') {

                    // 🔒 Amount mismatch check
                    $paidAmount  = (float) ($result['amount'] ?? 0);
                    $orderAmount = (float) (($order->advance_amount ?? 0) > 0 ? $order->advance_amount : $order->total_amount);

                    if ($paidAmount < ($orderAmount - 1)) {
                        Log::warning("Surjopay amount mismatch for order #{$orderId}: expected={$orderAmount}, paid={$paidAmount}");
                        return $this->redirectToOrderFail($order, $client, 'Payment amount mismatch. Please contact support.');
                    }

                    $order->update([
                        'payment_status'    => 'paid',
                        'payment_reference' => $result['bank_trx_id'] ?? $spOrderId,
                        'advance_amount'    => $paidAmount,
                    ]);

                    return $this->redirectToOrderSuccess($order, $client, 'Payment successful! Your order has been confirmed.');
                }

                Log::warning("Surjopay bank_status not Success for order #{$orderId}: " . ($result['bank_status'] ?? 'null'));
            }

        } catch (\Exception $e) {
            Log::error('Surjopay Verify Error: ' . $e->getMessage());
        }

        return $this->redirectToOrderFail($order, $client, 'Payment could not be verified. Please contact support.');
    }

    public function surjopayFail(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'failed']);
        }

        return $this->redirectToOrderFail($order, $order->client, 'Payment failed. Please try again.');
    }

    public function surjopayCancel(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'pending']);
        }

        return $this->redirectToOrderFail($order, $order->client, 'Payment cancelled.');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 📱 bKASH PGW — Official Tokenized Checkout API
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function initiateBkashPgw(Request $request, $orderId)
    {
        $order  = Order::with('client')->findOrFail($orderId);
        $client = $order->client;

        // 🔒 Gateway active check
        if (!$client->isPaymentGatewayActive('bkash_pgw')) {
            abort(403, 'bKash PGW is not enabled for this shop.');
        }

        // 🔒 Already paid হলে re-initiate করা যাবে না
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'This order is already paid.');
        }

        $config = $client->getPaymentGatewayConfig('bkash_pgw');
        $bkash  = new BkashPgwService();

        try {
            $callbackUrl = route('payment.bkash.pgw.callback', $orderId);
            $cancelUrl   = route('payment.bkash.pgw.callback', $orderId) . '?status=cancel';

            $result = $bkash->createPayment($config, $order, $callbackUrl, $cancelUrl);

            // 🔒 Store paymentID in order for later verification
            $order->update([
                'payment_method'    => 'bkash_pgw',
                'payment_status'    => 'pending',
                'payment_reference' => $result['paymentID'],
            ]);

            // Redirect customer to bKash checkout page
            return redirect($result['bkashURL']);

        } catch (\Exception $e) {
            Log::error('bKash PGW Init Error: ' . $e->getMessage(), ['order_id' => $orderId]);
            return $this->redirectToOrderFail($order, $client, 'bKash payment initialization failed. Please try again.');
        }
    }

    public function bkashPgwCallback(Request $request, $orderId)
    {
        $order  = Order::with('client')->findOrFail($orderId);
        $client = $order->client;

        // 🔒 Cancelled by user
        $status = $request->get('status', '');
        if ($status === 'cancel' || $status === 'failure') {
            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'pending']);
            }
            return $this->redirectToOrderFail($order, $client, 'bKash payment was cancelled or failed.');
        }

        // 🔒 paymentID must be present in callback
        $callbackPaymentID = $request->get('paymentID');
        if (empty($callbackPaymentID)) {
            Log::warning('bKash PGW: Callback missing paymentID', ['order_id' => $orderId, 'query' => $request->all()]);
            return $this->redirectToOrderFail($order, $client, 'Invalid callback from bKash.');
        }

        // 🔒 Already paid — idempotency guard
        if ($order->payment_status === 'paid') {
            return $this->redirectToOrderSuccess($order, $client, 'Your order is already confirmed.');
        }

        // 🔒 paymentID must match what we stored at initiation
        $storedPaymentID = $order->payment_reference;
        if ($storedPaymentID && $storedPaymentID !== $callbackPaymentID) {
            Log::warning('bKash PGW: paymentID mismatch', [
                'order_id' => $orderId,
                'stored'   => $storedPaymentID,
                'received' => $callbackPaymentID,
            ]);
            return $this->redirectToOrderFail($order, $client, 'Payment reference mismatch. Please contact support.');
        }

        // 🔒 Gateway still active
        if (!$client->isPaymentGatewayActive('bkash_pgw')) {
            return $this->redirectToOrderFail($order, $client, 'bKash payment gateway is not active.');
        }

        $config = $client->getPaymentGatewayConfig('bkash_pgw');
        $bkash  = new BkashPgwService();

        try {
            // ── Step 5: Execute Payment ──────────────────────────
            $executeData = $bkash->executePayment($config, $callbackPaymentID);

            // ── Security: Amount verification ────────────────────
            $bkash->verifyAmount($order, $executeData);

            // ── Security: Query & confirm Completed status ───────
            $bkash->verifyCompletion($config, $callbackPaymentID);

            // ── Update order ─────────────────────────────────────
            $order->update([
                'payment_status'    => 'paid',
                'payment_method'    => 'bkash_pgw',
                'payment_reference' => $executeData['trxID'] ?? $callbackPaymentID,
                'advance_amount'    => (float) ($executeData['amount'] ?? $bkash->resolveAmount($order)),
            ]);

            Log::info('bKash PGW: Payment successful', [
                'order_id'  => $orderId,
                'paymentID' => $callbackPaymentID,
                'trxID'     => $executeData['trxID'] ?? null,
            ]);

            return $this->redirectToOrderSuccess($order, $client, 'bKash payment successful! Your order is confirmed.');

        } catch (\Exception $e) {
            Log::error('bKash PGW Callback Error: ' . $e->getMessage(), [
                'order_id'  => $orderId,
                'paymentID' => $callbackPaymentID,
            ]);
            return $this->redirectToOrderFail($order, $client, 'bKash payment verification failed. Please contact support.');
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🔧 REDIRECT HELPERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    protected function redirectToOrderSuccess(Order $order, Client $client, string $message)
    {
        $slug     = $client->slug;
        $trackUrl = $slug
            ? route('shop.track', ['slug' => $slug])
            : url('/track');

        return redirect($trackUrl . '?order_id=' . $order->id)
            ->with('payment_success', $message)
            ->with('order_id', $order->id);
    }

    protected function redirectToOrderFail(Order $order, Client $client, string $message)
    {
        $slug     = $client->slug;
        $trackUrl = $slug
            ? route('shop.track', ['slug' => $slug])
            : url('/track');

        return redirect($trackUrl . '?order_id=' . $order->id)
            ->with('payment_error', $message)
            ->with('order_id', $order->id);
    }
}
