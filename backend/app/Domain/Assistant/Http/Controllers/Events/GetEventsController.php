<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Models\AssistantEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RRule\RRule;

class GetEventsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
            'type'   => 'nullable|string',
            'status' => 'nullable|string|in:active,cancelled,completed',
        ]);

        $userId = $request->user()->id;
        $from   = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfDay();
        $to     = isset($validated['to'])   ? Carbon::parse($validated['to'])->endOfDay()   : $from->copy()->addDays(30)->endOfDay();
        $status = $validated['status'] ?? null;

        $events = collect();

        // Single events
        $singleQuery = AssistantEvent::where('user_id', $userId)
            ->whereNull('series_id')
            ->whereNull('recurrence_rule')
            ->whereBetween('event_at', [$from, $to]);

        if ($status) {
            $singleQuery->where('status', $status);
        } else {
            $singleQuery->where('status', '!=', 'cancelled');
        }

        if (isset($validated['type'])) {
            $singleQuery->where('type', $validated['type']);
        }

        $events = $events->merge($singleQuery->with('reminders')->get());

        // Masters with recurrence
        $masters = AssistantEvent::where('user_id', $userId)
            ->whereNull('series_id')
            ->whereNotNull('recurrence_rule')
            ->where('status', '!=', 'cancelled')
            ->get();

        // Materialized occurrences in range
        $materialized = AssistantEvent::where('user_id', $userId)
            ->whereNotNull('series_id')
            ->whereBetween('event_at', [$from, $to])
            ->with('reminders')
            ->get()
            ->keyBy(fn ($e) => "{$e->series_id}_{$e->occurrence_at?->toDateString()}");

        foreach ($masters as $master) {
            try {
                $rrule       = new RRule($master->recurrence_rule, $master->event_at);
                $occurrences = $rrule->getOccurrencesBetween($from->toDateTime(), $to->toDateTime());
            } catch (\Throwable) {
                continue;
            }

            foreach ($occurrences as $dt) {
                $date = Carbon::instance($dt)->toDateString();
                $key  = "{$master->id}_{$date}";

                if (isset($materialized[$key])) {
                    $real = $materialized[$key];
                    if ($real->status === 'cancelled') {
                        continue; // skip cancelled exceptions
                    }
                    if (! $status || $real->status === $status) {
                        $events->push($real);
                    }
                } else {
                    if ($status && $status !== 'active') {
                        continue; // virtual occurrences are always active
                    }
                    $events->push((object) [
                        'id'        => "v_{$master->id}_{$date}",
                        'series_id' => $master->id,
                        'content'   => $master->content,
                        'event_at'  => Carbon::instance($dt)->toIso8601String(),
                        '_virtual'  => true,
                    ]);
                }
            }
        }

        $sorted = $events->sortBy(fn ($e) => is_object($e) && isset($e->_virtual)
            ? $e->event_at
            : (is_object($e) ? optional($e->event_at)->timestamp : 0)
        );

        $data = $sorted->map(function ($e) {
            if (is_object($e) && isset($e->_virtual) && $e->_virtual) {
                return [
                    'id'        => $e->id,
                    'virtual'   => true,
                    'series_id' => $e->series_id,
                    'content'   => $e->content,
                    'event_at'  => $e->event_at,
                    'reminders' => [],
                ];
            }

            return (new AssistantEventResource($e))->toArray(request());
        });

        return response()->json(['data' => $data->values()]);
    }
}
