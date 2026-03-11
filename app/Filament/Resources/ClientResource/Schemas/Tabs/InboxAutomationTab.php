<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Toggle;

class InboxAutomationTab
{
    public static function schema(): array
    {
        return [
            Section::make('AI Comment & Inbox Automation')
                ->description('ফেসবুক পেইজের কমেন্টে অটো-রিপ্লাই এবং ইনবক্স মেসেজ সেটআপ করুন।')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    Group::make()->schema([
                        Toggle::make('auto_comment_reply')
                            ->label('Auto Comment Reply')
                            ->helperText('AI নিজে থেকে কাস্টমারের কমেন্টের নিচে রিপ্লাই দিবে।')
                            ->default(true),

                        Toggle::make('auto_private_reply')
                            ->label('Auto Inbox Message (PM)')
                            ->helperText('কমেন্টকারীকে AI সরাসরি মেসেঞ্জারে মেসেজ পাঠাবে।')
                            ->default(true),
                    ])->columns(2),
                ]),
            
            Toggle::make('auto_status_update_msg')
                ->label('Auto Order Status SMS (Messenger/IG)')
                ->helperText('ড্যাশবোর্ড থেকে অর্ডারের স্ট্যাটাস পরিবর্তন করলে কাস্টমার অটোমেটিক মেসেজ পাবে।')
                ->default(true),
        ];
    }
}