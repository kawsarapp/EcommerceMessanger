<?php

namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;
use Illuminate\Support\Str;

class AddressStep implements OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array
    {
        $customerInfo = $session->customer_info ?? [];
        $productId = $customerInfo['product_id'] ?? null;
        
        // ক্লিন মেসেজ
        $cleanMessage = trim($userMessage);

        // =========================
        // 1️⃣ Phone Extraction (Priority)
        // =========================
        $phone = $this->extractPhoneNumber($cleanMessage);
        if ($phone) {
            $customerInfo['phone'] = $phone;
            // ফোন নম্বর বাদে বাকি অংশ ঠিকানার জন্য প্রসেস করব
            $cleanMessage = trim(str_replace($phone, '', $cleanMessage)); 
        }

        // =========================
        // 2️⃣ Name Extraction (Basic NLP)
        // =========================
        // যদি মেসেজে "Name:" বা "Nam:" থাকে অথবা ৩টি অংশের প্রথম অংশ নাম হয়
        $name = $this->extractName($cleanMessage);
        if ($name) {
            $customerInfo['name'] = $name;
            // নাম বাদে বাকি অংশ ঠিকানার জন্য রাখা
            $cleanMessage = trim(str_ireplace(["Name:", "Nam:", $name], '', $cleanMessage));
        }

        // =========================
        // 3️⃣ Address & Location Analysis
        // =========================
        if ($this->isValidAddress($cleanMessage)) {
            // আগের অ্যাড্রেসের সাথে নতুন তথ্য যোগ করা (যদি ইউজার ভেঙে ভেঙে দেয়)
            $existingAddress = $customerInfo['address'] ?? '';
            $newAddress = $existingAddress ? "$existingAddress, $cleanMessage" : $cleanMessage;
            
            $customerInfo['address'] = $newAddress;

            // 🔥 Location Intelligence (Dhaka vs Outside)
            $locationData = $this->analyzeLocation($newAddress);
            $customerInfo['location_type'] = $locationData['type']; // inside_dhaka / outside_dhaka
            $customerInfo['district'] = $locationData['district']; // Potential district
        }

        // Check completeness
        $hasPhone = !empty($customerInfo['phone']);
        $hasAddress = !empty($customerInfo['address']);

        // =========================
        // 4️⃣ Decision Logic
        // =========================
        if ($hasPhone && $hasAddress) {

            $customerInfo['step'] = 'confirm_order';
            $session->update(['customer_info' => $customerInfo]);

            // ডেলিভারি চার্জের হিন্টস তৈরি
            $locType = $customerInfo['location_type'] === 'inside_dhaka' ? 'ঢাকার ভেতরে' : 'ঢাকার বাইরে';
            
            return [
                'instruction' =>
                    "ফোন ({$customerInfo['phone']}) এবং ঠিকানা ({$customerInfo['address']}) পেয়েছি। লোকেশন ডিটেক্টেড: {$locType}। এখন অর্ডারের সামারি দেখিয়ে কনফার্ম করতে বলো।",
                'context' => json_encode([
                    'product_id' => $productId,
                    'name' => $customerInfo['name'] ?? 'Guest',
                    'phone' => $customerInfo['phone'],
                    'address' => $customerInfo['address'],
                    'location' => $locType
                ])
            ];
        }

        // Update session
        $session->update(['customer_info' => $customerInfo]);

        $missing = [];
        if (!$hasPhone) $missing[] = "ফোন নম্বর";
        if (!$hasAddress) $missing[] = "পূর্ণ ঠিকানা (জেলা ও থানা সহ)";

        return [
            'instruction' =>
                "অর্ডার প্রসেস করার জন্য " . implode(' এবং ', $missing) . " প্রয়োজন। বিনয়ের সাথে চাও।",
            'context' => json_encode([
                'product_id' => $productId,
                'captured_phone' => $customerInfo['phone'] ?? null,
                'captured_address' => $customerInfo['address'] ?? null
            ])
        ];
    }

    // =========================
    // Strict Address Validation (Advanced)
    // =========================
    private function isValidAddress(string $text): bool
    {
        $text = trim($text);
        if (empty($text)) return false;

        // নেগেটিভ কিওয়ার্ড চেক
        $invalidTriggers = [
            'price', 'dam', 'koto', 'picture', 'send', 'pic daw',
            'delivery charge', 'available', 'details', 'price koto',
            'ace', 'ase', 'আছে', 'product', 'pic', 'chobi', 'kobe pabo'
        ];

        $lower = mb_strtolower($text);
        foreach ($invalidTriggers as $trigger) {
            if (str_contains($lower, $trigger)) {
                return false;
            }
        }

        // 🔥 Smart Check: যদি ৫ ক্যারেক্টারের কম হয় কিন্তু ভ্যালিড লোকেশন কিওয়ার্ড থাকে, তবে গ্রহন করো
        // (আগে শুধু ১৫ ক্যারেক্টার চেক ছিল, এখন স্মার্ট করা হলো)
        $validLocationKeywords = ['dhaka', 'road', 'house', 'sector', 'block', 'zilla', 'thana', 'district', 'sadar', 'town', 'village', 'street', 'area', 'bazar', 'more'];
        
        foreach ($validLocationKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true; // ছোট হলেও ভ্যালিড
            }
        }

        // সাধারণ চেক (Length Based)
        if (mb_strlen($text) < 5) { // ১৫ থেকে কমিয়ে ৫ করা হলো যাতে "Dhaka" বা "Savar" এর মতো ছোট ইনপুট নেয়
            return false;
        }

        return true;
    }

    // =========================
    // BD Phone Extractor (Stable)
    // =========================
    private function extractPhoneNumber(string $msg): ?string
    {
        $bn = ["১","২","৩","৪","৫","৬","৭","৮","৯","০"];
        $en = ["1","2","3","4","5","6","7","8","9","0"];

        $msg = str_replace($bn, $en, $msg);
        // স্পেস এবং হাইফেন রিমুভ করে ক্লিন করা
        $digits = preg_replace('/[^0-9]/', '', $msg);

        if (preg_match('/01[3-9]\d{8}/', $digits, $matches)) {
            return substr($matches[0], 0, 11);
        }

        return null;
    }

    // =========================
    // 🔥 NEW: Name Extractor
    // =========================
    private function extractName(string $msg): ?string
    {
        // 1. Explicit Prefix Check
        if (preg_match('/(name|nam|naam)[:\s]+([a-zA-Z\s\x{0980}-\x{09FF}]+)/iu', $msg, $matches)) {
            return trim($matches[2]);
        }

        return null; // অটোমেটিক নাম বের করা রিস্কি, তাই আপাতত শুধু এক্সপ্লিসিট নাম ধরবে
    }

    // =========================
    // 🔥 NEW: Location Analyzer (Dhaka vs Outside)
    // =========================
    private function analyzeLocation(string $address): array
    {
        $lowerAddr = mb_strtolower($address);
        
        // ঢাকার ভেতরের কিওয়ার্ড
        $dhakaKeywords = [
            'dhaka', 'mirpur', 'uttara', 'banani', 'gulshan', 'dhanmondi', 
            'mohammadpur', 'badda', 'rampura', 'khilgaon', 'basabo', 'jatrabari', 
            'old dhaka', 'keraniganj', 'savar', 'motijheel', 'farmgate', 'tejgaon',
            'ঢাকা', 'মিরপুর', 'উত্তরা', 'বনানী', 'গুলশান', 'ধানমন্ডি', 'মোহাম্মদপুর'
        ];

        foreach ($dhakaKeywords as $area) {
            if (str_contains($lowerAddr, $area)) {
                return ['type' => 'inside_dhaka', 'district' => 'Dhaka'];
            }
        }

        return ['type' => 'outside_dhaka', 'district' => 'Other'];
    }
}