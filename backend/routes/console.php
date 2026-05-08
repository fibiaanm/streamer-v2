<?php

use App\Console\Commands\ExpireInvitationsCommand;
use App\Console\Commands\PruneFailedJobsCommand;
use App\Domain\Assistant\Jobs\SweepPendingReminders;
use App\Domain\Assistant\Jobs\SweepSeriesChains;
use App\Domain\Assistant\Jobs\SweepTokenUsageRollupJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireInvitationsCommand::class)
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new SweepTokenUsageRollupJob())
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::job(new SweepPendingReminders())
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::job(new SweepSeriesChains())
    ->dailyAt('05:00')
    ->withoutOverlapping();

Schedule::command(PruneFailedJobsCommand::class, ['--months=1'])
    ->monthly()
    ->withoutOverlapping();
