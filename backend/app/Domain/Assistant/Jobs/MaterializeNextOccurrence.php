<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Support\ReminderScheduler;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RRule\RRule;
use Throwable;

class MaterializeNextOccurrence implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $occurrenceId) {}

    public function handle(): void
    {
        $occurrence = AssistantEvent::with('user')->find($this->occurrenceId);

        if (! $occurrence || ! $occurrence->series_id || ! $occurrence->user) {
            return;
        }

        $master = AssistantEvent::find($occurrence->series_id);

        if (! $master || $master->status === 'cancelled') {
            return;
        }

        if ($master->series_ends_at && $master->series_ends_at->isPast()) {
            return;
        }

        $nextAt = $this->findNextFreeSlot($master, $occurrence->occurrence_at);

        if (! $nextAt) {
            return;
        }

        $next = AssistantEvent::create([
            'user_id'       => $master->user_id,
            'series_id'     => $master->id,
            'occurrence_at' => $nextAt,
            'event_at'      => $nextAt,
            'content'       => $master->content,
            'type'          => $master->type,
            'status'        => 'active',
        ]);

        $timezone = $occurrence->user->timezone ?? 'UTC';

        ReminderScheduler::scheduleForEvent($next, $timezone);
    }

    private function findNextFreeSlot(AssistantEvent $master, Carbon $after): ?Carbon
    {
        $cancelledSlots = AssistantEvent::where('series_id', $master->id)
            ->where('status', 'cancelled')
            ->pluck('occurrence_at')
            ->map(fn ($d) => Carbon::parse($d)->toDateTimeString())
            ->flip()
            ->all();

        $activeSlots = AssistantEvent::where('series_id', $master->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('occurrence_at')
            ->map(fn ($d) => Carbon::parse($d)->toDateTimeString())
            ->flip()
            ->all();

        $current = $after;

        for ($i = 0; $i < 500; $i++) {
            $nextAt = $this->nextOccurrenceAfter($master, $current);

            if (! $nextAt) {
                return null;
            }

            if ($master->series_ends_at && $nextAt->gt($master->series_ends_at)) {
                return null;
            }

            $key = $nextAt->toDateTimeString();

            if (isset($activeSlots[$key])) {
                return null; // already materialized — idempotent stop
            }

            if (isset($cancelledSlots[$key]) || $nextAt->isPast()) {
                $current = $nextAt;
                continue;
            }

            return $nextAt;
        }

        return null;
    }

    private function nextOccurrenceAfter(AssistantEvent $master, Carbon $after): ?Carbon
    {
        try {
            $rrule       = new RRule($master->recurrence_rule, $master->event_at);
            $occurrences = $rrule->getOccurrencesBetween(
                $after->copy()->addSecond()->toDateTime(),
                $after->copy()->addYears(2)->toDateTime(),
            );

            if ($occurrences) {
                return Carbon::instance(reset($occurrences));
            }
        } catch (Throwable $e) {
            Log::warning('assistant.materialize_next_rrule_error', [
                'occurrence_id' => $this->occurrenceId,
                'exception'     => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function failed(Throwable $exception): void
    {
        Log::error('assistant.materialize_next_failed', [
            'occurrence_id' => $this->occurrenceId,
            'exception'     => $exception->getMessage(),
        ]);
    }
}
