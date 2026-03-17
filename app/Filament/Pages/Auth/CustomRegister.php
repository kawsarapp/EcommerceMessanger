<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomRegister extends Register
{
    public function mount(): void
    {
        parent::mount();
    }

    /**
     * রেজিস্ট্রেশন ফর্মের স্কিমা
     * URL-এ ?plan=ID থাকলে সেই plan pre-select হবে
     */
    public function form(Form $form): Form
    {
        $plans = Plan::where('is_active', true)->orderBy('price', 'asc')->get();
        $selectedPlanId = request('plan');

        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),

                // Plan selection (optional — admin assign করতে পারবে পরে)
                Select::make('plan_id')
                    ->label('Choose Your Plan')
                    ->options($plans->pluck('name', 'id')->toArray())
                    ->default($selectedPlanId)
                    ->searchable()
                    ->helperText('আপনার বাজেট অনুযায়ী প্ল্যান বেছে নিন। পরে পরিবর্তন করা যাবে।')
                    ->visible($plans->count() > 0),
            ])
            ->statePath('data');
    }

    /**
     * রেজিস্ট্রেশন সম্পন্ন হওয়ার পর ইউজার ও ক্লায়েন্ট প্রোফাইল তৈরি করা
     */
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            try {
                // ১. প্রথমে ইউজার তৈরি হবে (Laravel Default)
                $user = parent::handleRegistration($data);
                
                // Set explicitly as a seller
                $user->update(['role' => 'seller']);

                // ২. এরপর এই ইউজারের জন্য ১টি শপ (Client Profile) তৈরি হবে
                Client::create([
                    'user_id'  => $user->id,

                    // ডিফল্ট শপ নেম
                    'shop_name' => $user->name . "'s Shop",

                    // ইউনিক স্লাগ (Name + Random Number)
                    'slug' => Str::slug($user->name . '-' . rand(1000, 9999)),

                    // plan selection হলে assign করবে, না হলে null
                    'plan_id'      => !empty($data['plan_id']) ? $data['plan_id'] : null,
                    'plan_ends_at' => !empty($data['plan_id']) ? now()->addMonths(1) : null,

                    // ইনিশিয়াল স্ট্যাটাস ও সেটিংস
                    'status' => 'inactive',

                    'delivery_charge_inside'  => 80,
                    'delivery_charge_outside' => 150,
                ]);

                Log::info("✅ New Merchant Registered: {$user->email}" . (!empty($data['plan_id']) ? " | Plan ID: {$data['plan_id']}" : " | No Plan Selected"));

                return $user;

            } catch (\Exception $e) {
                Log::error("❌ Registration Failed: " . $e->getMessage());
                throw $e;
            }
        });
    }
}