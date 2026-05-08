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

        if (! $occurrence || ! $occurrence->series_id) {
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
        $current = $after;
        $skips   = 0;

        while ($skips < 10) {
            $nextAt = $this->nextOccurrenceAfter($master, $current);

            if (! $nextAt) {
                return null;
            }

            if ($master->series_ends_at && $nextAt->gt($master->series_ends_at)) {
                return null;
            }

            $existing = AssistantEvent::where('series_id', $master->id)
                ->where('occurrence_at', $nextAt->toDateTimeString())
                ->first();

            if (! $existing) {
                return $nextAt;
            }

            if ($existing->status !== 'cancelled') {
                return null; // already active or completed — idempotent stop
            }

            // cancelled slot — skip and look for the next one
            $current = $nextAt;
            $skips++;
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
