<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    foreach (Storage::disk('public')->files('reports') as $file) {
        if (
            Carbon::createFromTimestamp(
                Storage::disk('public')->lastModified($file)
            )->lt(now()->subHours(12))
        ) {
            Storage::disk('public')->delete($file);
        }
    }
})->hourly()->name('clean-old-reports');
