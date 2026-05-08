<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SweepSeriesChains implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        AssistantEvent::where('status', 'active')
            ->whereNotNull('series_id')
            ->where('event_at', '<', now())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('assistant_events as child')
                    ->whereColumn('child.series_id', 'assistant_events.series_id')
                    ->whereColumn('child.occurrence_at', '>', 'assistant_events.occurrence_at')
                    ->whereNull('child.deleted_at');
            })
            ->each(function (AssistantEvent $occurrence) {
                MaterializeNextOccurrence::dispatch($occurrence->id)->onQueue('assistant-series');
            });
    }
}
