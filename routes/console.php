<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tracker:check-timeouts')->everyMinute();
Schedule::command('tracker:update-connectivity')->everyMinute();
Schedule::command('tracker:aggregate-hourly')->hourly();
Schedule::command('tracker:purge-retention')->daily();
