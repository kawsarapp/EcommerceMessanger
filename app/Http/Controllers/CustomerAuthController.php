<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\Shop\ShopClientService;

class CustomerAuthController extends Controller
{
    protected $clientService;

    public function __construct(ShopClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function showLoginForm(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');

        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);

        return view('shop.auth.login', compact('client', 'clean', 'baseUrl'));
    }

    public function showRegisterForm(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');

        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);

        return view('shop.auth.register', compact('client', 'clean', 'baseUrl'));
    }

    public function login(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);

        $request->validate([
            'login'    => 'required|string', // phone or email
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $customer = Customer::where('client_id', $client->id)
                            ->where($loginField, $request->login)
                            ->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'login' => ['তথ্যগুলো সঠিক নয়।'],
            ]);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        
        // Handle post-login redirect (e.g. from checkout)
        if ($request->has('redirect_to')) {
            return redirect($request->redirect_to);
        }

        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $dashboardUrl = $clean ? 'https://'.$clean.'/customer/dashboard' : route('shop.customer.dashboard', $client->slug);

        return redirect()->to($dashboardUrl);
    }

    public function register(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);

        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|min:11|max:15|unique:customers,phone,NULL,id,client_id,' . $client->id,
            'email'    => 'nullable|email|max:255|unique:customers,email,NULL,id,client_id,' . $client->id,
            'password' => 'required|string|min:6|confirmed',
        ]);

        $customer = Customer::create([
            'client_id' => $client->id,
            'name'      => $request->name,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        Auth::guard('customer')->login($customer);

        // Handle post-register redirect (e.g. from checkout)
        if ($request->has('redirect_to')) {
            return redirect($request->redirect_to);
        }

        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $dashboardUrl = $clean ? 'https://'.$clean.'/customer/dashboard' : route('shop.customer.dashboard', $client->slug);

        return redirect()->to($dashboardUrl)->with('success', 'একাউন্ট সফলভাবে তৈরি হয়েছে!');
    }

    public function logout(Request $request, $slug = null)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $client = $this->clientService->getSafeClient($request, $slug);
        $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
        $homeUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);

        return redirect()->to($homeUrl);
    }
}
