<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\ReminderRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SweepPendingReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        ReminderRun::where('status', 'pending')
            ->where('run_at', '<=', now())
            ->each(fn ($run) => FireReminderRun::dispatch($run->id));
    }
}
