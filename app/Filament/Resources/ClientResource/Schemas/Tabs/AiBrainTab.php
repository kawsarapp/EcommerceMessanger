<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class AiBrainTab
{
    public static function schema(): array
    {
        return [
            // ─── AI Model: Only Super Admin can see & change ───────────────────
            Section::make('🤖 AI Model Selection')
                ->description('এই স্টোরের জন্য কোন AI ব্যবহার হবে তা সিলেক্ট করুন।')
                ->visible(fn () => auth()->user()?->isSuperAdmin())
                ->schema([
                    Select::make('ai_model')
                        ->label('AI Model সিলেক্ট করুন')
                        ->options([
                            'gemini-pro'              => '🟦 Google Gemini 1.5 Flash (Default – Fast)',
                            'gemini-pro-full'         => '🟦 Google Gemini 2.0 Flash (Latest & Powerful)',
                            'gemini-2.5-flash'        => '🟦 Google Gemini 2.5 Flash',
                            'gemini-2.5-pro'          => '🟦 Google Gemini 2.5 Pro',
                            'gemini-2.5-flash-lite'   => '🟦 Google Gemini 2.5 Flash-Lite',
                            'gemini-3.1-pro-preview'  => '🟦 Google Gemini 3.1 Pro Preview',
                            'gemini-3.1-flash-lite-preview' => '🟦 Google Gemini 3.1 Flash Lite Preview',
                            'gemini-2.5-flash-image'  => '🖼️ Nano Banana (Image Variant)',
                            'gpt-4o'                  => '🟩 OpenAI GPT-4o (Premium)',
                            'gpt-4o-mini'             => '🟩 OpenAI GPT-4o Mini (Cost-effective)',
                            'gpt-3.5-turbo'           => '🟩 OpenAI GPT-3.5 Turbo (Legacy)',
                            'claude-3-opus-20240229'  => '🟧 Claude 3 Opus (Most Powerful)',
                            'claude-3-haiku-20240307' => '🟧 Claude 3 Haiku (Fastest)',
                            'deepseek-chat'           => '🟪 DeepSeek V3 (Chat)',
                            'deepseek-reasoner'       => '🟪 DeepSeek R1 (Advanced Reasoning)',
                        ])
                        ->default('gemini-pro')
                        ->required()
                        ->helperText('⚙️ Admin only — Seller এই সেটিং দেখতে বা পরিবর্তন করতে পারবে না।')
                        ->live(),

                    \Filament\Forms\Components\TextInput::make('gemini_api_key')
                        ->label('Google Gemini API Key')
                        ->password()
                        ->placeholder('AIzaSy...')
                        ->helperText('কী না দিলে সিস্টেমের ডিফল্ট কী ব্যবহার হবে।')
                        ->visible(fn (callable $get) => str_starts_with($get('ai_model') ?? '', 'gemini')),

                    \Filament\Forms\Components\TextInput::make('openai_api_key')
                        ->label('OpenAI API Key (ChatGPT)')
                        ->password()
                        ->placeholder('sk-proj-...')
                        ->helperText('কী না দিলে সিস্টেমের ডিফল্ট কী ব্যবহার হবে।')
                        ->visible(fn (callable $get) => str_starts_with($get('ai_model') ?? '', 'gpt')),

                    \Filament\Forms\Components\TextInput::make('deepseek_api_key')
                        ->label('DeepSeek API Key')
                        ->password()
                        ->placeholder('sk-...')
                        ->helperText('কী না দিলে সিস্টেমের ডিফল্ট কী ব্যবহার হবে।')
                        ->visible(fn (callable $get) => str_starts_with($get('ai_model') ?? '', 'deepseek')),

                    \Filament\Forms\Components\TextInput::make('claude_api_key')
                        ->label('Anthropic Claude API Key')
                        ->password()
                        ->placeholder('sk-ant-...')
                        ->helperText('কী না দিলে সিস্টেমের ডিফল্ট কী ব্যবহার হবে।')
                        ->visible(fn (callable $get) => str_starts_with($get('ai_model') ?? '', 'claude')),

                    \Filament\Forms\Components\TextInput::make('groq_api_key')
                        ->label('Groq Fast API Key')
                        ->password()
                        ->placeholder('gsk_...')
                        ->helperText('কী না দিলে সিস্টেমের ডিফল্ট কী ব্যবহার হবে।')
                        ->visible(fn (callable $get) => str_starts_with($get('ai_model') ?? '', 'groq')),
                ]),

            // ─── Knowledge Base: Seller can edit ──────────────────────────────
            Section::make('Knowledge Base')
                ->description('দোকানের নিয়মকানুন এখানে লিখুন। AI এটি পড়েই কাস্টমারকে উত্তর দিবে।')
                ->schema([
                    Textarea::make('knowledge_base')
                        ->label('Shop Policies & FAQs')
                        ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ৮০ টাকা।\n২. রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ।")
                        ->rows(6),
                ]),

            // ─── Bot Personality: Seller can edit ─────────────────────────────
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

            // ─── MESSAGE BATCHING ────────────────────────────────────────────────
            Section::make('🧩 Message Batching')
                ->description(
                    'Customer যদি দ্রুত অনেকগুলো ভাগ ভাগ message পাঠায় তাহলে AI সবগুলোকে একসাথে join করে process করবে। ' .
                    'যত ms wait নির্ধারণ করবেন, তত সময় নতুন message এলে timer reset হয়। মিনিমাম 500ms, ম্যাক্স 10000ms।'
                )
                ->schema([
                    Toggle::make('message_batch_enabled')
                        ->label('Enable Message Batching')
                        ->helperText('On করলে AI কিছুক্ষণ wait করে সব message join করে process করবে।')
                        ->default(false)
                        ->reactive(),

                    Select::make('message_batch_delay_ms')
                        ->label('Wait Duration (milliseconds)')
                        ->helperText('নতুন message না এলে যতক্ষণ পরে AI উত্তর দেবে')
                        ->options([
                            500  => '500ms  (0.5 সেকেন্ড — অতি দ্রুত)',
                            1000 => '1000ms (1 সেকেন্ড — দ্রুত)',
                            1500 => '1500ms (1.5 সেকেন্ড)',
                            2000 => '2000ms (2 সেকেন্ড — প্রস্তাবিত) ⭐',
                            3000 => '3000ms (3 সেকেন্ড)',
                            4000 => '4000ms (4 সেকেন্ড)',
                            5000 => '5000ms (5 সেকেন্ড — পরিশ্রমী কাস্টমারদের জন্য)',
                        ])
                        ->default(2000)
                        ->required()
                        ->visible(fn (callable $get) => (bool) $get('message_batch_enabled')),
                ])->columns(2),
        ];
    }
}