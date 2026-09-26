<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Run by the hPanel cron every minute: php artisan schedule:run.

// Heartbeat: proves the cron is alive, and says which PHP ran it. The version matters:
// the hPanel cron decides it, the web server is 8.3, and a scheduled command that fails names the
// binary it used (26 Sep: one still read php82 while the panel was set to 8.3). Shown on /admin/fekdi.
Schedule::call(function () {
    \App\Models\AppSetting::put('scheduler_heartbeat', now()->toIso8601String());
    \App\Models\AppSetting::put('scheduler_php', PHP_VERSION.' ('.PHP_BINARY.')');
})->everyMinute()->name('scheduler-heartbeat');

// FEKDI x IFSE (24–27 Sep 2026). Both do nothing that reaches the client while the admin switch is off.
// A race whose START code sets a time limit ends when it runs out, even if nobody is looking:
// /race/status and the station gate close it on contact, this catches the phones that went quiet.
Schedule::command('race:close-expired')->everyMinute()->withoutOverlapping(5);

Schedule::command('fekdi:sync')->everyMinute()->withoutOverlapping(10);
Schedule::command('fekdi:import')->hourly()->withoutOverlapping(30);

// Routes drawn in the tracker app. New ones arrive switched off until the crew picks them.
Schedule::command('tracker:sync-map')->hourly()->withoutOverlapping(30);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
