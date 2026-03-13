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
            Section::make('AI Model Selection (Admin Only)')
                ->description('Choose which AI to power this specific store.')
                ->schema([
                    Select::make('ai_model')
                        ->label('Select AI Model')
                        ->options([
                            'gemini-pro' => 'Google Gemini Pro (Default)',
                            'gpt-4o' => 'OpenAI GPT-4o',
                            'gpt-3.5-turbo' => 'OpenAI GPT-3.5 Turbo',
                            'claude-3-opus' => 'Anthropic Claude 3 Opus',
                        ])
                        ->default('gemini-pro')
                        ->required()
                        ->visible(fn () => auth()->user()?->isSuperAdmin()), // Only Superadmin can change
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