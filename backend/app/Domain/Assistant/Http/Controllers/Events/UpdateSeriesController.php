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
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateSeriesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'   => 'nullable|string',
            'type'      => 'nullable|string',
            'event_end' => 'nullable|date',
            'time'      => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $scalarFields = array_filter(
            array_intersect_key($validated, array_flip(['content', 'type', 'event_end'])),
            fn ($v) => $v !== null,
        );

        $newTime = $validated['time'] ?? null;

        if (empty($scalarFields) && $newTime === null) {
            abort(422, 'At least one field must be provided.');
        }

        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $userId   = $request->user()->id;
        $resolved = EventResolver::resolve($eventId, $userId);
        $master   = $this->resolveMaster($resolved);
        $timezone = $master->user?->timezone ?? 'UTC';

        if (isset($scalarFields['event_end'])) {
            $scalarFields['event_end'] = Carbon::parse($scalarFields['event_end'])->toDateTimeString();
        }

        $exceptions = [];

        DB::transaction(function () use ($master, $scalarFields, $newTime, $timezone, &$exceptions) {
            // Update master scalar fields
            if ($scalarFields) {
                $master->fill($scalarFields)->save();
            }

            // Update master event_at time if requested
            if ($newTime !== null) {
                [$h, $m] = explode(':', $newTime);
                $masterLocal = $master->event_at->copy()->setTimezone($timezone)
                    ->setHours((int) $h)->setMinutes((int) $m)->setSeconds(0);
                $master->update(['event_at' => $masterLocal->utc()]);
            }

            // Future active materialized occurrences
            $occurrences = AssistantEvent::with('user')
                ->where('series_id', $master->id)
                ->where('event_at', '>', now())
                ->where('status', 'active')
                ->get();

            foreach ($occurrences as $occ) {
                $wasMoved = $occ->event_at->toDateString() !== $occ->occurrence_at->toDateString();

                if ($wasMoved) {
                    // Exception — inform but do not touch
                    $exceptions[] = [
                        'id'            => $occ->getHashId(),
                        'event_at'      => $occ->event_at->toIso8601String(),
                        'occurrence_at' => $occ->occurrence_at->toIso8601String(),
                    ];
                    continue;
                }

                $updateFields = $scalarFields;

                if ($newTime !== null) {
                    [$h, $m] = explode(':', $newTime);
                    $occLocal = $occ->event_at->copy()->setTimezone($timezone)
                        ->setHours((int) $h)->setMinutes((int) $m)->setSeconds(0);
                    $updateFields['event_at'] = $occLocal->utc()->toDateTimeString();
                }

                if ($updateFields) {
                    ReminderScheduler::releaseByEventIds([$occ->id]);
                    $occ->update($updateFields);
                    $occ->refresh();
                    ReminderScheduler::scheduleForEvent($occ, $timezone);
                } else {
                    $occ->update($scalarFields);
                }
            }
        });

        return response()->json([
            'data'       => (new AssistantEventResource($master->fresh()))->toArray($request),
            'exceptions' => $exceptions,
        ]);
    }

    private function resolveMaster($resolved): AssistantEvent
    {
        if ($resolved->isVirtual()) {
            return $resolved->master();
        }

        $model = $resolved->model();

        if ($model->isMaster()) {
            return $model;
        }

        if ($model->isOccurrence()) {
            return $model->master ?? abort(422, 'Occurrence has no master series.');
        }

        abort(422, 'Event is not part of a series.');
    }
}
