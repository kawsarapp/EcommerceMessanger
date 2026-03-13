<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class AiBrainTab
{
    public static function schema(): array
    {
        return [
            Section::make('🤖 AI Model Selection (Admin Only)')
                ->description('এই স্টোরের জন্য কোন AI ব্যবহার হবে সেটি এখান থেকে সিলেক্ট করুন। API Key সার্ভারের .env ফাইলে থাকে।')
                ->schema([
                    Select::make('ai_model')
                        ->label('AI Model সিলেক্ট করুন')
                        ->options([
                            // ── Google Gemini ──────────────────────────
                            'gemini-pro'      => '🟦 Google Gemini 1.5 Flash (Default – Fast)',
                            'gemini-pro-full' => '🟦 Google Gemini 2.0 Flash (Latest & Powerful)',

                            // ── OpenAI ────────────────────────────────
                            'gpt-4o'          => '🟩 OpenAI GPT-4o (Best Quality)',
                            'gpt-4o-mini'     => '🟩 OpenAI GPT-4o Mini (Cheap & Fast)',
                            'gpt-3.5-turbo'   => '🟩 OpenAI GPT-3.5 Turbo (Budget)',

                            // ── Anthropic Claude ─────────────────────
                            'claude-3-opus-20240229'   => '🟧 Anthropic Claude 3 Opus (Smartest)',
                            'claude-3-haiku-20240307'  => '🟧 Anthropic Claude 3 Haiku (Fast)',

                            // ── DeepSeek ──────────────────────────────
                            'deepseek-chat'     => '🟪 DeepSeek Chat (Cheap & Powerful)',
                            'deepseek-reasoner' => '🟪 DeepSeek R1 (Reasoning Model)',
                        ])
                        ->default('gemini-pro')
                        ->required()
                        ->helperText('⚙️ API Key গুলো Server এর .env ফাইলে সেট করা আছে। এখানে শুধু মডেল assign করুন।')
                        ->visible(fn () => auth()->user()?->isSuperAdmin()),
                ])->visible(fn () => auth()->user()?->isSuperAdmin()),

            Section::make('Knowledge Base')
                ->description('দোকানের নিয়মকানুন এখানে লিখুন। AI এটি পড়েই কাস্টমারকে উত্তর দিবে।')
                ->schema([
                    Textarea::make('knowledge_base')
                        ->label('Shop Policies & FAQs')
                        ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ৮০ টাকা।\n২. রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ।")
                        ->rows(6),
                ]),

            Section::make('Bot Personality')
                ->description('Advanced: AI behavior control.')
                ->collapsed()
                ->schema([
                    Textarea::make('custom_prompt')
                        ->label('Salesman Personality')
                        ->placeholder("তুমি একজন ভদ্র সেলসম্যান। কাস্টমারকে 'স্যার' বলে সম্বোধন করবে...")
                        ->rows(3),
                ]),

            Section::make('Abandoned Cart Automation')
                ->description('অসম্পূর্ণ অর্ডারগুলো রিকভার করতে এআই রিমাইন্ডার সেটআপ করুন।')
                ->schema([
                    Toggle::make('is_reminder_active')
                        ->label('Enable AI Follow-up')
                        ->onColor('success')
                        ->offColor('danger')
                        ->inline(false),
                    
                    Select::make('reminder_delay_hours')
                        ->label('Send Reminder After')
                        ->options([1 => '1 Hour', 2 => '2 Hours', 6 => '6 Hours', 12 => '12 Hours', 24 => '24 Hours'])
                        ->default(2)
                        ->required()
                        ->visible(fn (callable $get) => $get('is_reminder_active')),
                ])->columns(2),

            Section::make('Post-Purchase Auto Review')
                ->description('অর্ডার ডেলিভারি হওয়ার পর কাস্টমারের কাছ থেকে অটোমেটিক রিভিউ সংগ্রহ করুন।')
                ->schema([
                    Toggle::make('is_review_collection_active')
                        ->label('Enable Auto Review Request')
                        ->default(true),
                    
                    Select::make('review_delay_days')
                        ->label('Ask for review after (Days)')
                        ->options([1 => '1 Day', 2 => '2 Days', 3 => '3 Days', 5 => '5 Days', 7 => '7 Days'])
                        ->default(3)
                        ->required()
                        ->visible(fn (callable $get) => $get('is_review_collection_active')),
                ])->columns(2),
        ];
    }
}