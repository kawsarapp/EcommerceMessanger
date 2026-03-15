<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login;
use Illuminate\Validation\ValidationException;

class CustomLogin extends Login
{
    /**
     * 🔒 Filament-এর default authenticate() override করা হলো।
     * এখানে domain isolation check যোগ করা হয়েছে।
     * ভুল domain বা ভুল credentials হলে instant inline error দেখাবে।
     */
    public function authenticate(): ?LoginResponse
    {
        // Rate limiting (inherited)
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();

        // ১. Credentials check — ভুল হলে সাথে সাথে inline error
        if (!Filament::auth()->attempt(
            $this->getCredentialsFromFormData($data),
            $data['remember'] ?? false
        )) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        // ২. canAccessPanel check (handles is_active + domain isolation from User model)
        if (!$user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        // ৩. Extra domain isolation layer (wrong seller domain → specific error message)
        $host = request()->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);

        if ($host !== $mainDomain && $host !== '127.0.0.1' && $host !== 'localhost') {
            if (!$user->isSuperAdmin()) {
                $client = $user->client;
                if (!$client || $client->custom_domain !== $host) {
                    Filament::auth()->logout();
                    throw ValidationException::withMessages([
                        'data.email' => 'এই স্টোরের সাথে এই credentials মিলছে না।',
                    ]);
                }
            }
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
