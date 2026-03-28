<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cart:remind')->everyFiveMinutes();

// 🚚 Live Courier Automated Background Synchronization (Runs Every Hour)
Schedule::command('orders:sync-courier')->hourly();

// প্রতি সোমবার রাত ৩টায় database cleanup (auto, non-interactive)
Schedule::command('system:optimize --db')->weeklyOn(1, '03:00');

// প্রতি মাসের ১ তারিখে image optimization
Schedule::command('system:optimize --images')->monthlyOn(1, '02:00');

// Laravel built-in cleanup
Schedule::command('queue:prune-batches --hours=48')->daily();
