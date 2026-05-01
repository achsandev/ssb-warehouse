<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune audit log API (retention 90 hari) — berjalan otomatis di server
// yang cron schedule:run-nya aktif. Aman jika tidak ada: perintah tetap
// bisa dijalankan manual via `php artisan api:prune-logs`.
Schedule::command('api:prune-logs')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
