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
        // 2️⃣ Name Extraction (Enhanced NLP)
        // =========================
        // A. Explicit Extraction (Name: Rahim)
        $explicitName = $this->extractName($cleanMessage);
        if ($explicitName) {
            $customerInfo['name'] = $explicitName;
            $cleanMessage = trim(str_ireplace(["Name:", "Nam:", "Naam:", "আমার নাম", "My name is", $explicitName], '', $cleanMessage));
        } 
        // B. 🔥 Smart Fallback: যদি ফোন নম্বর আগে থেকেই থাকে, কিন্তু নাম না থাকে, 
        // এবং মেসেজটি খুব ছোট হয় (ঠিকানা নয়), তবে এটিই নাম হওয়ার সম্ভাবনা বেশি।
        elseif (!empty($customerInfo['phone']) && empty($customerInfo['name']) && !$this->isValidAddress($cleanMessage) && mb_strlen($cleanMessage) > 2 && mb_strlen($cleanMessage) < 20) {
            // নাম হিসেবে সেভ করা হচ্ছে
            $customerInfo['name'] = $cleanMessage;
            $cleanMessage = ""; // নাম নিয়ে নিলাম, তাই ক্লিয়ার করে দিলাম
        }

        // =========================
        // 3️⃣ Payment Method Detection (Bonus Feature)
        // =========================
        $paymentMethod = $this->detectPaymentIntent($cleanMessage);
        if ($paymentMethod) {
            $customerInfo['payment_method'] = $paymentMethod;
        }

        // =========================
        // 4️⃣ Address & Location Analysis
        // =========================
        if ($this->isValidAddress($cleanMessage)) {
            // আগের অ্যাড্রেসের সাথে নতুন তথ্য যোগ করা (যদি ইউজার ভেঙে ভেঙে দেয়)
            $existingAddress = $customerInfo['address'] ?? '';
            
            // ডুপ্লিকেট এড়ানো
            if (!str_contains($existingAddress, $cleanMessage)) {
                $newAddress = $existingAddress ? "$existingAddress, $cleanMessage" : $cleanMessage;
                $customerInfo['address'] = $newAddress;

                // 🔥 Location Intelligence (Dhaka vs Outside & District Detection)
                $locationData = $this->analyzeLocation($newAddress);
                
                $customerInfo['location_type'] = $locationData['type']; // inside_dhaka / outside_dhaka
                $customerInfo['district'] = $locationData['district']; // Specific District Name
                $customerInfo['division'] = $locationData['division'] ?? null; // Division info
            }
        }

        // =========================
        // 5️⃣ Completeness Check (Strict)
        // =========================
        $hasName = !empty($customerInfo['name']);
        $hasPhone = !empty($customerInfo['phone']);
        $hasAddress = !empty($customerInfo['address']);

        // সেশন আপডেট
        $session->update(['customer_info' => $customerInfo]);

        // ✅ সব তথ্য থাকলে কনফার্মেশন স্টেপে পাঠাও
        if ($hasName && $hasPhone && $hasAddress) {
            $customerInfo['step'] = 'confirm_order';
            $session->update(['customer_info' => $customerInfo]);

            // ডেলিভারি চার্জের হিন্টস তৈরি
            $locType = $customerInfo['location_type'] === 'inside_dhaka' ? 'ঢাকার ভেতরে' : 'ঢাকার বাইরে';
            $districtTxt = $customerInfo['district'] !== 'Other' ? "({$customerInfo['district']})" : "";
            
            return [
                'instruction' => 
                    "নাম ({$customerInfo['name']}), ফোন ({$customerInfo['phone']}) এবং ঠিকানা পেয়েছি। লোকেশন: {$locType} {$districtTxt}। এখন অর্ডারের সামারি দেখিয়ে কনফার্ম করতে বলো।",
                'context' => json_encode($customerInfo)
            ];
        }

        // ❌ কিছু মিসিং থাকলে স্পেসিফিক ভাবে চাও
        $missing = [];
        if (!$hasName) $missing[] = "আপনার নাম"; // নাম আগে চেক করবে
        if (!$hasPhone) $missing[] = "মোবাইল নম্বর";
        if (!$hasAddress) $missing[] = "পূর্ণ ঠিকানা (জেলা ও থানা সহ)";

        $missingStr = implode(' এবং ', $missing);

        return [
            'instruction' => 
                "সতর্কতা: অর্ডার কনফার্ম করার জন্য এখনো [ {$missingStr} ] পাওয়া যায়নি।
                কাস্টমারকে বলো: 'দয়া করে আপনার {$missingStr} দিন।'
                ⚠️ এই স্টেপে ভুলেও 'Confirm' বা 'Ji' লিখতে বলবে না। আগে তথ্য নাও, তারপর কনফার্মেশন চাইবে।",
            'context' => json_encode([
                'missing_fields' => $missing,
                'captured_info' => $customerInfo
            ])
        ];
    }

    // =========================
    // Helpers
    // =========================

    /**
     * স্মার্ট অ্যাড্রেস ভ্যালিডেশন
     */
    private function isValidAddress(string $text): bool
    {
        $text = trim($text);
        if (empty($text)) return false;

        // নেগেটিভ কিওয়ার্ড চেক (Greeting বা Question যাতে ঠিকানা হিসেবে না ধরে)
        $invalidTriggers = [
            'price', 'dam', 'koto', 'picture', 'send', 'pic daw',
            'delivery charge', 'available', 'details', 'price koto',
            'ace', 'ase', 'আছে', 'product', 'pic', 'chobi', 'kobe pabo',
            'hello', 'hi', 'kemon acen', 'order korbo', 'nibo', 'shirt'
        ];

        $lower = mb_strtolower($text);
        foreach ($invalidTriggers as $trigger) {
            if (str_contains($lower, $trigger)) {
                return false;
            }
        }

        // 🔥 Smart Check: যদি ৫ ক্যারেক্টারের কম হয় কিন্তু ভ্যালিড লোকেশন কিওয়ার্ড থাকে
        $validLocationKeywords = [
            'dhaka', 'road', 'house', 'sector', 'block', 'zilla', 'thana', 'district', 
            'sadar', 'town', 'village', 'street', 'area', 'bazar', 'more', 'flat', 'floor',
            'বাসা', 'রোড', 'থানা', 'জেলা', 'গ্রাম', 'ফ্ল্যাট', 'office'
        ];
        
        foreach ($validLocationKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true; 
            }
        }

        // সাধারণ চেক (Length Based)
        if (mb_strlen($text) < 5) { 
            return false;
        }

        return true;
    }

    /**
     * ফোন নম্বর বের করা (Bangla & English Digit Support)
     */
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

    /**
     * 🔥 নাম বের করা (More Patterns Added)
     */
    private function extractName(string $msg): ?string
    {
        // 1. Explicit Prefix Check
        // Supports: Name: X, Amar nam X, My name is X
        if (preg_match('/(name|nam|naam|amar nam|my name is)[:\s]+([a-zA-Z\s\x{0980}-\x{09FF}]+)/iu', $msg, $matches)) {
            return trim($matches[2]);
        }

        return null; 
    }

    /**
     * 🔥 পেমেন্ট ইনটেন্ট ডিটেকশন (New Feature)
     */
    private function detectPaymentIntent(string $msg): ?string
    {
        $msg = mb_strtolower($msg);
        if (str_contains($msg, 'cash') || str_contains($msg, 'cod') || str_contains($msg, 'home delivery')) {
            return 'cod';
        }
        if (str_contains($msg, 'bkash') || str_contains($msg, 'bikash')) {
            return 'bkash';
        }
        if (str_contains($msg, 'nagad')) {
            return 'nagad';
        }
        return null;
    }

    /**
     * 🔥 লোকেশন অ্যানালাইজার (Advanced District Detection)
     */
    private function analyzeLocation(string $address): array
    {
        $lowerAddr = mb_strtolower($address);
        
        // ১. ঢাকার ভেতরের কিওয়ার্ড (Priority)
        $dhakaKeywords = [
            'dhaka', 'mirpur', 'uttara', 'banani', 'gulshan', 'dhanmondi', 
            'mohammadpur', 'badda', 'rampura', 'khilgaon', 'basabo', 'jatrabari', 
            'old dhaka', 'keraniganj', 'savar', 'motijheel', 'farmgate', 'tejgaon',
            'ঢাকা', 'মিরপুর', 'উত্তরা', 'বনানী', 'গুলশান', 'ধানমন্ডি', 'মোহাম্মদপুর'
        ];

        foreach ($dhakaKeywords as $area) {
            if (str_contains($lowerAddr, $area)) {
                return ['type' => 'inside_dhaka', 'district' => 'Dhaka', 'division' => 'Dhaka'];
            }
        }

        // ২. 🔥 জেলা ডিটেকশন (৬৪টি জেলা)
        // এটি ডাটাবেস বা কনফিগ ফাইল থেকেও আনা যেতে পারে, তবে পারফরমেন্সের জন্য এখানে হার্ডকোড করা হলো
        $districts = [
            'chittagong' => 'Chittagong', 'chatogram' => 'Chittagong', 'চট্টগ্রাম' => 'Chittagong',
            'sylhet' => 'Sylhet', 'সিলেট' => 'Sylhet',
            'khulna' => 'Khulna', 'খুলনা' => 'Khulna',
            'rajshahi' => 'Rajshahi', 'রাজশাহী' => 'Rajshahi',
            'barisal' => 'Barisal', 'barishal' => 'Barisal', 'বরিশাল' => 'Barisal',
            'rangpur' => 'Rangpur', 'রংপুর' => 'Rangpur',
            'mymensingh' => 'Mymensingh', 'ময়মনসিংহ' => 'Mymensingh',
            'comilla' => 'Comilla', 'cumilla' => 'Comilla', 'কুমিল্লা' => 'Comilla',
            'gazipur' => 'Gazipur', 'গাজীপুর' => 'Gazipur',
            'narayanganj' => 'Narayanganj', 'নারায়ণগঞ্জ' => 'Narayanganj',
            'bogra' => 'Bogra', 'bogan' => 'Bogra', 'বগুড়া' => 'Bogra',
            'cox' => 'Cox\'s Bazar', 'coxs bazar' => 'Cox\'s Bazar', 'কক্সবাজার' => 'Cox\'s Bazar',
            'jessore' => 'Jessore', 'jashore' => 'Jessore', 'যশোর' => 'Jessore',
            'feni' => 'Feni', 'ফেনী' => 'Feni',
            'tangail' => 'Tangail', 'টাঙ্গাইল' => 'Tangail',
            'pabna' => 'Pabna', 'পাবনা' => 'Pabna',
            'noakhali' => 'Noakhali', 'নোয়াখালী' => 'Noakhali'
            // প্রয়োজনে আরও জেলা যোগ করা যাবে
        ];

        foreach ($districts as $key => $name) {
            if (str_contains($lowerAddr, $key)) {
                // জেলা পাওয়া গেলে ঢাকার বাইরে হিসেবে মার্ক করা হবে
                return ['type' => 'outside_dhaka', 'district' => $name, 'division' => 'Other'];
            }
        }

        // ৩. ডিফল্ট (যদি কিছু না মেলে)
        return ['type' => 'outside_dhaka', 'district' => 'Other', 'division' => 'Other'];
    }
}