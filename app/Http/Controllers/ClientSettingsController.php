<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientSettingsController extends Controller
{
    // ডোমেইন পেজ ভিউ
    public function domainPage()
    {
        $client = Auth::user()->client; // রিলেশনশিপ ধরে ক্লায়েন্ট আনা
        return view('dashboard.settings.domain', compact('client'));
    }

    // ডোমেইন সেভ করা
    public function updateDomain(Request $request)
    {
        $request->validate([
            'custom_domain' => 'required|string|unique:clients,custom_domain,' . Auth::user()->client_id
        ]);

        // ডোমেইন থেকে http/https বা স্ল্যাশ রিমুভ করা
        $domain = str_replace(['http://', 'https://', '/'], '', $request->custom_domain);

        $client = Auth::user()->client;
        $client->update(['custom_domain' => $domain]);

        return back()->with('success', 'Domain updated successfully! Please point your DNS to our server IP.');
    }
}