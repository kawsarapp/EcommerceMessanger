<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SafetyGuardService
{
    /**
     * মেসেজটি নিরাপদ কি না চেক করা
     * Return: 'safe', 'bad_word', 'angry', 'spam'
     */
    public function checkMessageSafety($userId, $message)
    {
        $message = strtolower(trim($message));

        // ১. খারাপ ভাষা চেক (Bad Words)
        $badWords = config('safety.bad_words', []);
        foreach ($badWords as $word) {
            if (str_contains($message, $word)) {
                Log::warning("🚫 Bad Word Detected from User $userId: $word");
                return 'bad_word';
            }
        }

        // ২. কাস্টমার রেগে আছে কি না (Angry/Frustrated Check)
        $angryWords = config('safety.angry_words', []);
        foreach ($angryWords as $word) {
            if (str_contains($message, $word)) {
                Log::info("😡 Angry Customer Detected User $userId: $word");
                return 'angry';
            }
        }

        // ৩. একই মেসেজ বারবার দেওয়া (Spam/Loop Check)
        if ($this->isSpamming($userId, $message)) {
            Log::warning("🔄 Loop/Spam Detected from User $userId");
            return 'spam';
        }

        return 'safe';
    }

    /**
     * স্প্যাম বা লুপ চেক করার লজিক
     */
    private function isSpamming($userId, $message)
    {
        $cacheKey = "last_msg_hash_{$userId}";
        $countKey = "repeat_count_{$userId}";

        $currentHash = md5($message);
        $lastHash = Cache::get($cacheKey);

        if ($currentHash === $lastHash) {
            $count = Cache::increment($countKey);
            // কনফিগ থেকে লিমিট চেক (ডিফল্ট ৩ বার)
            if ($count >= config('safety.max_repeats', 3)) {
                return true;
            }
        } else {
            // নতুন মেসেজ আসলে কাউন্টার রিসেট
            Cache::put($cacheKey, $currentHash, 600); // ১০ মিনিট মনে রাখবে
            Cache::put($countKey, 1, 600);
        }

        return false;
    }
}