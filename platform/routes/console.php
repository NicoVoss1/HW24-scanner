<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('hw24:price-archive-purge', function () {
    try {
        $count=\App\Services\AmazonPriceArchiveRuntime::purge();
        $this->info('HW24 expired price snapshots removed: '.$count);
        return 0;
    } catch (\Throwable) {
        $this->error('HW24 price archive purge unavailable; capture stays gated without fresh heartbeat.');
        return 1;
    }
})->purpose('Purge expired HW24 price observations and refresh private heartbeat');
