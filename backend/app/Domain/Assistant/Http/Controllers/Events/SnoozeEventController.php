<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Support\EventResolver;
use App\Domain\Assistant\Support\ReminderScheduler;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RRule\RRule;
use Throwable;

class SnoozeEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'until' => 'required|date',
        ]);

        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $userId   = $request->user()->id;
        $timezone = $request->user()->timezone ?? 'UTC';
        $resolved = EventResolver::resolve($eventId, $userId);
        $until    = Carbon::parse($validated['until']);

        $master = $resolved->isVirtual()
            ? $resolved->master()
            : ($resolved->model()->master ?? abort(422, 'Cannot snooze a non-recurring event.'));

        $nextAt = $this->nextOccurrenceAfter($master, $until);

        if (! $nextAt) {
            abort(422, 'No future occurrences found after the given until date.');
        }

        if ($resolved->isVirtual()) {
            $resolved->materialize(['status' => 'cancelled']);
        } else {
            $model = $resolved->model();
            ReminderScheduler::releaseByEventIds([$model->id]);
            $model->update(['status' => 'cancelled']);
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

        ReminderScheduler::scheduleForEvent($next, $timezone);

        $next->load('reminders');

        return (new AssistantEventResource($next))->response()->setStatusCode(200);
    }

    private function nextOccurrenceAfter(AssistantEvent $master, Carbon $after): ?Carbon
    {
        try {
            $rrule       = new RRule($master->recurrence_rule, $master->event_at);
            $end         = $after->copy()->addYear();
            $occurrences = $rrule->getOccurrencesBetween($after->toDateTime(), $end->toDateTime());

            foreach ($occurrences as $dt) {
                $candidate = Carbon::instance($dt);
                if ($candidate->gt($after)) {
                    return $candidate;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
