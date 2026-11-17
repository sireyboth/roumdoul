<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// «Your invitation expires soon» emails, 14 days ahead (App\Console\Commands\RemindExpiringInvitations).
// Needs the scheduler running in production: `* * * * * php artisan schedule:run`.
Schedule::command('invitations:remind-expiring')->dailyAt('09:00');
