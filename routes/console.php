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
| MEASURED 2026-08-23 on the production container, first real full walk:
|   51,445 games, ~1.55 games/sec, so roughly NINE HOURS end to end.
|   ~325,000 rows written. Memory flat at ~117 MB.
| The binding constraint is speedrun.com's own 99 requests/60s, not our speed,
| so there is nothing to tune here - only when to start it.
|
| 03:30, not 03:00. The host's backup timer fires between 03:15 and 03:30
| (OnCalendar 03:15 + RandomizedDelaySec 900) and takes about a minute, so 03:30
| starts after it rather than during it. Since a nine-hour walk overlaps the
| NEXT day's backup regardless, this only buys a clean start - but a clean start
| is what makes a failed run easy to attribute.
|
| withoutOverlapping(60 * 20) is 1200 MINUTES - twenty hours, not twenty
| minutes. That is deliberate and it has to stay larger than a full walk, or
| tomorrow's pass starts on top of today's and both hammer the same rate limit.
| If the walk ever grows past twenty hours, raise this first.
|
| onOneServer() keeps it to a single container if the app is ever scaled out.
| runInBackground() so a nine-hour command does not block the rest of the
| schedule - it is the only entry today, but that will not always be true.
|
| NOTE ON THE RUNNER: none of this fires unless something invokes
| `php artisan schedule:run` every minute. In production that is a Coolify
| scheduled task, NOT a cron inside the image and NOT a detached docker exec -
| a detached exec dies with the next deploy. See speedrunwr#18.
|
*/
Schedule::command('speedrunwr:sync')
    ->dailyAt('03:30')
    ->withoutOverlapping(60 * 20)
    ->onOneServer()
    ->runInBackground();
