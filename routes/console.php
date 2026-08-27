<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('meta:check-connections')->daily();
Schedule::command('meta:sync-campaigns')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('meta:sync-insights')->hourly()->withoutOverlapping();
Schedule::command('meta:sync-insights --completed')->daily()->withoutOverlapping();
Schedule::command('meta:sync-account-snapshots')->everySixHours()->withoutOverlapping();
