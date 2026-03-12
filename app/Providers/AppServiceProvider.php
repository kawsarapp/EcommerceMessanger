<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        \App\Models\Order::observe(\App\Observers\OrderObserver::class);

        // Production বা ngrok হলে HTTPS force
        if ($this->app->environment('production') || str_contains(config('app.url'), 'ngrok')) {

            URL::forceScheme('https');
        }

        // OS detect করে Horizon disable (Windows)
        if (PHP_OS_FAMILY === 'Windows') {
            config(['horizon.use' => false]);
        }
    }
}