<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $files = Storage::disk('public')->files('reports');

    foreach ($files as $file) {
        $age = now()->diffInHours(Storage::disk('public')->lastModified($file));

        if ($age >= 24) {
            Storage::disk('public')->delete($file);
        }
    }
})->hourly()->name('clean-old-reports');
