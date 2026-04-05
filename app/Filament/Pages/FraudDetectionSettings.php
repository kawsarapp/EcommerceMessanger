<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class FraudDetectionSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = '⚙️ Settings & Tools';
    protected static ?string $navigationLabel = 'Fraud Detection API';
    protected static ?int    $navigationSort  = 8;
    protected static string  $view = 'filament.pages.fraud-detection-settings';

    // ── Live state ─────────────────────────────────────────────────────────────
    public string $apiKey    = '';
    public ?array $planData  = null;
    public ?array $connData  = null;
    public bool   $tested    = false;
    public bool   $connOk    = false;

    public function mount(): void
    {
        $this->apiKey = env('BDCOURIER_API_KEY', '');
    }

    // Only super admin can see this
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Header Actions ──────────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test Connection')
                ->icon('heroicon-o-wifi')
                ->color('info')
                ->action(fn () => $this->testConnection()),

            Action::make('checkPlan')
                ->label('My Plan')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->action(fn () => $this->fetchPlan()),
        ];
    }

    // ── Test Connection ─────────────────────────────────────────────────────────
    public function testConnection(): void
    {
        $key = config('services.bdcourier.api_key');

        if (empty($key)) {
            Notification::make()
                ->title('❌ No API Key')
                ->body('Please save a valid API key first.')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        try {
            $response = Http::withToken($key)
                ->timeout(8)
                ->get('https://api.bdcourier.com/check-connection');

            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? '') === 'success') {
                $data = $json['data'] ?? [];
                $this->connData = $data;
                $this->connOk   = true;
                $this->tested   = true;

                Notification::make()
                    ->title('✅ Connection Successful')
                    ->body("Connected as User ID: {$data['user_id']}\nServer Time: {$data['server_time']}")
                    ->success()
                    ->persistent()
                    ->send();
            } else {
                $this->connOk = false;
                $this->tested = true;

                Notification::make()
                    ->title('❌ Connection Failed')
                    ->body($json['message'] ?? 'API returned an error. Check your API key.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->connOk = false;
            $this->tested = true;

            Notification::make()
                ->title('❌ API Unreachable')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Fetch Plan ──────────────────────────────────────────────────────────────
    public function fetchPlan(): void
    {
        $key = config('services.bdcourier.api_key');

        if (empty($key)) {
            Notification::make()
                ->title('❌ No API Key')
                ->body('Please save a valid API key first.')
                ->danger()
                ->send();
            return;
        }

        try {
            $response = Http::withToken($key)
                ->timeout(8)
                ->get('https://api.bdcourier.com/my-plan');

            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? '') === 'success') {
                $this->planData = $json['data'] ?? [];

                Notification::make()
                    ->title('📋 Plan Loaded')
                    ->body('Your subscription plan details are shown below.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Could Not Fetch Plan')
                    ->body($json['message'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ API Unreachable')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Save API Key (writes to .env) ───────────────────────────────────────────
    public function saveApiKey(): void
    {
        $newKey = trim($this->apiKey);

        if (empty($newKey)) {
            Notification::make()
                ->title('⚠️ Empty Key')
                ->body('API key cannot be empty.')
                ->warning()
                ->send();
            return;
        }

        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            Notification::make()
                ->title('❌ .env Not Found')
                ->body('Cannot locate the .env file.')
                ->danger()
                ->send();
            return;
        }

        $envContent = File::get($envPath);
        $oldKey = env('BDCOURIER_API_KEY', '');

        if (!empty($oldKey)) {
            // Replace existing key
            $envContent = preg_replace(
                '/^BDCOURIER_API_KEY=.*/m',
                "BDCOURIER_API_KEY={$newKey}",
                $envContent
            );
        } else {
            // Append new key
            $envContent .= "\nBDCOURIER_API_KEY={$newKey}\n";
        }

        File::put($envPath, $envContent);

        // Also update running config so test works immediately
        config(['services.bdcourier.api_key' => $newKey]);

        Notification::make()
            ->title('✅ API Key Saved')
            ->body('The BDCourier API key has been updated in your .env file.')
            ->success()
            ->persistent()
            ->send();
    }

    public function getTitle(): string
    {
        return '🛡️ Fraud Detection API Settings';
    }
}
