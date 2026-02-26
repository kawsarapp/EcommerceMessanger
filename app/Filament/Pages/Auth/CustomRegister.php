<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomRegister extends Register
{
    /**
     * রেজিস্ট্রেশন ফর্মের স্কিমা
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                
                // নোট: এখানে প্ল্যান সিলেকশন রাখা হয়নি যাতে অ্যাডমিন ভেরিফাই করে প্ল্যান অ্যাসাইন করতে পারে।
            ])
            ->statePath('data');
    }

    /**
     * রেজিস্ট্রেশন সম্পন্ন হওয়ার পর ইউজার ও ক্লায়েন্ট প্রোফাইল তৈরি করা
     */
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            try {
                // ১. প্রথমে ইউজার তৈরি হবে (Laravel Default)
                $user = parent::handleRegistration($data);

                // ২. এরপর এই ইউজারের জন্য ১টি শপ (Client Profile) তৈরি হবে
                Client::create([
                    'user_id' => $user->id,
                    
                    // ডিফল্ট শপ নেম
                    'shop_name' => $user->name . "'s Shop",
                    
                    // ইউনিক স্লাগ (Name + Random Number)
                    'slug' => Str::slug($user->name . '-' . rand(1000, 9999)),
                    
                    // ইনিশিয়াল স্ট্যাটাস ও সেটিংস
                    'status' => 'inactive',
                    'plan_id' => null,     
                    'plan_ends_at' => null,
                    
                    'delivery_charge_inside' => 80,
                    'delivery_charge_outside' => 150,
                ]);

                Log::info("✅ New Merchant Registered: {$user->email}");

                return $user;

            } catch (\Exception $e) {
                Log::error("❌ Registration Failed: " . $e->getMessage());
                throw $e; // এরর হলে ট্রানজেকশন রোলব্যাক হবে
            }
        });
    }
}