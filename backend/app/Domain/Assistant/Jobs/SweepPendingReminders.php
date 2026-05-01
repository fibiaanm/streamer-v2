<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SweepPendingReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        EventReminder::where('status', 'pending')
            ->where('fire_at', '<=', now())
            ->each(fn ($reminder) => FireEventReminder::dispatch($reminder->id));
    }
}
