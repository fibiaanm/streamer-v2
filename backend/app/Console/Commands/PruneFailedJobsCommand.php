<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneFailedJobsCommand extends Command
{
    protected $signature   = 'failed-jobs:prune {--months=1 : Delete records older than this many months}';
    protected $description = 'Delete failed_jobs records older than the given number of months';

    public function handle(): void
    {
        $months    = (int) $this->option('months');
        $threshold = now()->subMonths($months);

        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $threshold)
            ->delete();

        $this->info("Pruned {$deleted} failed job(s) older than {$months} month(s).");
    }
}
