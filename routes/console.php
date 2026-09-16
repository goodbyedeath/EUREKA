<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Run by the hPanel cron every minute: php artisan schedule:run.

// Heartbeat: proves the cron is alive. Shown on /admin/fekdi.
Schedule::call(fn () => \App\Models\AppSetting::put('scheduler_heartbeat', now()->toIso8601String()))
    ->everyMinute()->name('scheduler-heartbeat');

// FEKDI x IFSE (24–27 Sep 2026). Both do nothing that reaches the client while the admin switch is off.
Schedule::command('fekdi:sync')->everyMinute()->withoutOverlapping(10);
Schedule::command('fekdi:import')->hourly()->withoutOverlapping(30);

// Routes drawn in the tracker app. New ones arrive switched off until the crew picks them.
Schedule::command('tracker:sync-map')->hourly()->withoutOverlapping(30);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
