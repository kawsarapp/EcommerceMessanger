<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
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
                Section::make('ব্যক্তিগত তথ্য (Seller Information)')
                    ->description('দয়া করে আপনার সঠিক ব্যক্তিগত তথ্য দিন।')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone')
                            ->label('আপনার মোবাইল নাম্বার (Phone Number)')
                            ->required()
                            ->tel()
                            ->helperText('সঠিক নম্বর দিন, প্রয়োজনে যোগাযোগ করা হবে।'),
                        TextInput::make('nid_number')
                            ->label('এনআইডি নম্বর (NID Number)')
                            ->helperText('আপনার জাতীয় পরিচয় পত্রের নম্বর (ঐচ্ছিক)।'),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])->columns(2),

                Section::make('ব্যবসার তথ্য (Business Information)')
                    ->description('অনলাইন শপ তৈরি করার জন্য আপনার ব্যবসার কিছু তথ্য দিন।')
                    ->schema([
                        TextInput::make('shop_name')
                            ->label('আপনার শপের নাম (Shop Name)')
                            ->required()
                            ->helperText('যে নামে আপনার অনলাইন স্টোর তৈরি হবে।'),
                        TextInput::make('facebook_page_link')
                            ->label('ফেসবুক পেজ লিংক (Facebook Page Link)')
                            ->url()
                            ->helperText('আপনার অনলাইন ব্যবসার ফেসবুক পেজের লিংক (যদি থাকে)।'),
                        TextInput::make('business_phone')
                            ->label('ব্যবসার ফোন নম্বর (Business Phone Number)')
                            ->tel()
                            ->helperText('কাস্টমারদের সাথে যোগাযোগের জন্য ফোন নম্বর।'),
                        TextInput::make('address')
                            ->label('অফিস/ব্যবসার ঠিকানা (Office/Business Address)')
                            ->helperText('আপনার ব্যবসার মূল ঠিকানা বা আপনার এলাকার নাম।'),
                        Select::make('business_age')
                            ->label('কতদিন ধরে ব্যবসা করছেন? (Business Age)')
                            ->options([
                                'new' => 'নতুন শুরু করছি',
                                'less_than_1_year' => '১ বছরের কম সময়',
                                '1_to_3_years' => '১ থেকে ৩ বছর',
                                'more_than_3_years' => '৩ বছরের বেশি সময়',
                            ])
                            ->helperText('অনলাইন ব্যবসার অভিজ্ঞতা।'),
                        TextInput::make('reference_phone')
                            ->label('রেফারেন্স ফোন নম্বর (Reference Phone)')
                            ->tel()
                            ->helperText('কেউ রেফার করে থাকলে তার মোবাইল নম্বর (ঐচ্ছিক)।'),
                    ])->columns(2),

                Section::make('প্ল্যান নির্বাচন এবং শর্তাবলী')
                    ->schema([
                        Select::make('plan_id')
                            ->label('প্ল্যান নির্বাচন করুন (Choose Your Plan)')
                            ->options($plans->pluck('name', 'id')->toArray())
                            ->default($selectedPlanId)
                            ->searchable()
                            ->required()
                            ->helperText('আপনার বাজেট অনুযায়ী প্ল্যান বেছে নিন।')
                            ->visible($plans->count() > 0),
                            
                        Checkbox::make('terms_accepted')
                            ->label('আমি সব শর্তাবলীর সাথে একমত (I agree to Terms & Conditions)')
                            ->required()
                            ->accepted()
                            ->helperText('আমাদের টার্মস এবং কন্ডিশন মেনে অ্যাকাউন্ট তৈরি করুন।'),
                    ]),
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
                $user->update([
                    'role' => 'seller',
                    'phone' => $data['phone'] ?? null,
                    'nid_number' => $data['nid_number'] ?? null,
                ]);

                // ২. এরপর এই ইউজারের জন্য ১টি শপ (Client Profile) তৈরি হবে
                $shopName = $data['shop_name'] ?? $user->name . "'s Shop";
                Client::create([
                    'user_id'  => $user->id,

                    // ডিফল্ট শপ নেম
                    'shop_name' => $shopName,

                    // ইউনিক স্লাগ (Name + Random Number)
                    'slug' => Str::slug($shopName . '-' . rand(1000, 9999)),

                    // Business Info
                    'facebook_page_link' => $data['facebook_page_link'] ?? null,
                    'phone' => $data['business_phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'business_age' => $data['business_age'] ?? null,
                    'reference_phone' => $data['reference_phone'] ?? null,

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