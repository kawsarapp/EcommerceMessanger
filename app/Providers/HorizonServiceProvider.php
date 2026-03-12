<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */


    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            // Ekhane apnar system er admin (User ID 1) ke allow kore din
            return $user->id === 1; 
            
            // Athoba apni chaile email diyeo allow korte paren:
            // return in_array($user->email, [
            //     'admin@asianhost.net',
            // ]);
        });
    }

}
