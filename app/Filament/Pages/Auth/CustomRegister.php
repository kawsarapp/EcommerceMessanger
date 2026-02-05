<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use Illuminate\Support\Str;

class CustomRegister extends Register
{
    /**
     * রেজিস্ট্রেশন ফর্মের স্কিমা (এখানে প্ল্যান সিলেকশন রাখা হয়নি)
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                
                // এখানে ইচ্ছা করেই Plan Select অপশন রাখা হয়নি
                // যাতে ক্লায়েন্ট নিজে নিজে প্ল্যান নিতে না পারে
            ])
            ->statePath('data');
    }

    /**
     * রেজিস্ট্রেশন সম্পন্ন হওয়ার পর ক্লায়েন্ট প্রোফাইল তৈরি করা
     */
    protected function handleRegistration(array $data): Model
    {
        // ১. প্রথমে ইউজার তৈরি হবে (Laravel Default)
        $user = parent::handleRegistration($data);

        // ২. এরপর অটোমেটিক ক্লায়েন্ট প্রোফাইল তৈরি হবে (Pending অবস্থায়)
        Client::create([
            'user_id' => $user->id,
            
            // শপ নেম ডিফল্টভাবে ইউজারের নাম দিয়ে তৈরি হবে (পরে এডিট করা যাবে)
            'shop_name' => $user->name . "'s Shop",
            
            // ইউনিক স্লাগ তৈরি
            'slug' => Str::slug($user->name . '-' . time()),
            
            // ⚠️ গুরত্বপূর্ণ: স্ট্যাটাস Inactive এবং প্ল্যান Null থাকবে
            'status' => 'inactive', 
            'plan_id' => null,      
            'plan_ends_at' => null, 
            
            // ডিফল্ট সেটিংস
            'delivery_charge_inside' => 80,
            'delivery_charge_outside' => 150,
        ]);

        return $user;
    }
}