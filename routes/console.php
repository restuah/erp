<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Penjadwalan harian sinkronisasi kurs Bank Indonesia (setiap hari pukul 10:00 WIB).
 */
Schedule::command('exchange-rates:sync')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

