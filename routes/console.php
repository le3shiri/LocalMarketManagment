<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Daily stock housekeeping. In production add the single cron entry:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('stock:block-expired')->dailyAt('00:05')->withoutOverlapping();
