<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


/**
 * execute articles fetching every night at 12:00 AM
 */
Schedule::command('articles:fetch --source=newsapi')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('articles:fetch --source=guardian')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('articles:fetch --source=nytimes')->dailyAt('00:00')->withoutOverlapping();
