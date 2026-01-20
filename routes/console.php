<?php

use App\Models\Exam;
use Carbon\Carbon;
// use Illuminate\Foundation\Inspiring;
// use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/* Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly(); */

Schedule::call(function () {
    Exam::query()
        ->where('publish_status', 0)
        ->whereDate('exp_date', '<', Carbon::today())
        ->update([
            'publish_status' => 1,
        ]);
})->dailyAt('23:59');
