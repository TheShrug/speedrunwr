<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| The Python scraper ran from a bare crontab entry once a day. Same cadence,
| but owned by the app so it shows up in `artisan schedule:list`.
|
| A full walk is tens of thousands of API calls at 99 requests/60s, so it takes
| hours. withoutOverlapping() with a generous expiry stops a slow pass from
| having tomorrow's pass start on top of it; onOneServer() keeps it to a single
| container if the app is ever scaled out.
|
*/
Schedule::command('speedrunwr:sync')
    ->dailyAt('03:00')
    ->withoutOverlapping(60 * 20)
    ->onOneServer()
    ->runInBackground();
