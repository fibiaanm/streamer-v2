<?php

namespace App\Console\Commands;

use App\Domain\Assistant\Jobs\SweepTokenUsageRollupJob;
use Illuminate\Console\Command;

class SweepUsageRollupCommand extends Command
{
    protected $signature   = 'usage:sweep';
    protected $description = 'Run the token usage rollup sweep immediately';

    public function handle(): int
    {
        $this->info('Running SweepTokenUsageRollupJob…');

        (new SweepTokenUsageRollupJob())->handle();

        $this->info('Done.');
        return self::SUCCESS;
    }
}
