<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('clientloop:generate-tasks')->hourly()->withoutOverlapping();
Schedule::command('clientloop:launch-campaigns')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('clientloop:expire-trials')->hourly()->withoutOverlapping();
