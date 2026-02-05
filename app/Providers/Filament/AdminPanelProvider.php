<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomRegister; // আপনার কাস্টম রেজিস্ট্রেশন পেজ

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            
            // --- [ Branding & UI Customization ] ---
            ->brandName('AI Commerce Bot') // অ্যাপের নাম
            ->font('Inter') // মডার্ন ফন্ট
            ->favicon(asset('favicon.ico')) // আপনার ফ্যাভিকন
            ->colors([
                'primary' => Color::Blue, // SaaS এর জন্য ব্লু কালার বেশি প্রফেশনাল
            ])
            ->sidebarCollapsibleOnDesktop() // সাইডবার ছোট-বড় করার সুবিধা
            ->maxContentWidth('full') // ফুল স্ক্রিন ভিউ
            
            // --- [ Authentication ] ---
            ->login()
            ->registration(CustomRegister::class) // কাস্টম রেজিস্ট্রেশন কানেক্ট করা হলো
            ->passwordReset()
            ->emailVerification()
            ->profile() // প্রোফাইল এডিট করার সুবিধা

            // --- [ Resources & Pages ] ---
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])

            // --- [ Widgets ] ---
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class, // এটি কমেন্ট করা হলো যাতে ডকুমেন্টেশন লিংক না দেখায়
            ])

            // --- [ Middleware ] ---
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            // --- [ Advanced: Hide Filament Branding via CSS ] ---
            ->renderHook(
                'panels::head.end',
                fn (): string => '<style>
                    /* ফুটার এবং গিটহাব লিংক হাইড করা */
                    .fi-footer, .fi-sidebar-footer { display: none !important; }
                    .fi-topbar-item-github { display: none !important; }
                    
                    /* পেন্ডিং স্ট্যাটাস ব্যাজের জন্য কাস্টম স্টাইল */
                    .ring-red-500 { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
                    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
                </style>'
            );
    }
}