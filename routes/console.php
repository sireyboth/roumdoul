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

// Morning business summary to Telegram (App\Console\Commands\SendDailySummary) — on Phnom Penh time.
// Turn off with TELEGRAM_DAILY_SUMMARY=false; change the time with TELEGRAM_DAILY_SUMMARY_AT=07:30.
Schedule::command('report:daily')
    ->dailyAt(config('services.telegram.daily_summary_at', '08:00'))
    ->timezone(config('roumdoul.event_timezone', 'Asia/Phnom_Penh'))
    ->when(fn () => filter_var(config('services.telegram.daily_summary'), FILTER_VALIDATE_BOOL));
