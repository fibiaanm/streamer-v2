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

class UpdateEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'   => 'nullable|string',
            'event_at'  => 'nullable|date',
            'event_end' => 'nullable|date',
            'type'      => 'nullable|string',
        ]);

        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $userId   = $request->user()->id;
        $timezone = $request->user()->timezone ?? 'UTC';
        $resolved = EventResolver::resolve($eventId, $userId);

        if ($resolved->isVirtual()) {
            $overrides = array_filter($validated, fn ($v) => $v !== null);
            $model     = $resolved->materialize($overrides);
            ReminderScheduler::scheduleForEvent($model, $timezone);
        } else {
            $model      = $resolved->model();
            $oldEventAt = $model->event_at?->copy();

            $model->fill(array_filter($validated, fn ($v) => $v !== null));
            $model->save();

            if (isset($validated['event_at']) && $model->event_at != $oldEventAt) {
                ReminderScheduler::releaseByEventIds([$model->id]);
                ReminderScheduler::scheduleForEvent($model, $timezone);
            }
        }

        $model->load('reminders');

        return (new AssistantEventResource($model))->response()->setStatusCode(200);
    }
}
