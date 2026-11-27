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

Artisan::command('hw24:research-enqueue {ean} {--confirm}', function () {
    if (!$this->option('confirm')) { $this->error('Explicit TEST confirmation required'); return 1; }
    try {
        $v=\App\Services\ResearchQueueRuntime::queue()->enqueue((string)$this->argument('ean'),'operator_cli');
        $this->line(json_encode($v,JSON_THROW_ON_ERROR));
        return 0;
    } catch (\Throwable) { $this->error('Research enqueue unavailable or GTIN invalid.'); return 1; }
})->purpose('Queue a single confirmed TEST GTIN without provider calls');

Artisan::command('hw24:research-work-once {--confirm}', function () {
    if (!$this->option('confirm')) { $this->error('Explicit TEST confirmation required'); return 1; }
    try {
        $worker=\App\Services\ResearchQueueRuntime::worker(app(\App\Services\LegacyReadBridge::class));
        $v=$worker->runOne('worker_cli');
        $this->line(json_encode($v??['status'=>'empty_or_paused'],JSON_THROW_ON_ERROR));
        return 0;
    } catch (\Throwable) { $this->error('Research worker unavailable; no blind retry.'); return 1; }
})->purpose('Run at most one authorized TEST research job');
