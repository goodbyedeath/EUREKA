<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Run by the hPanel cron: * * * * * php artisan schedule:run
// FEKDI x IFSE (24–27 Sep 2026). Both do nothing that reaches the client while the admin switch is off.
Schedule::command('fekdi:sync')->everyMinute()->withoutOverlapping(10);
Schedule::command('fekdi:import')->hourly()->withoutOverlapping(30);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
