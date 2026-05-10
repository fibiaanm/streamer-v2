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

        // Masters with recurrence — bounded by window to use idx_events_masters
        $masters = AssistantEvent::where('user_id', $userId)
            ->whereNull('series_id')
            ->whereNotNull('recurrence_rule')
            ->where('status', '!=', 'cancelled')
            ->where('event_at', '<=', $to)
            ->where(fn ($q) => $q->whereNull('series_ends_at')
                                 ->orWhere('series_ends_at', '>=', $from))
            ->get();

        // Materialized occurrences in range — load by canonical slot OR by actual date if moved
        $materializedRows = AssistantEvent::where('user_id', $userId)
            ->whereNotNull('series_id')
            ->where(fn ($q) => $q
                ->whereBetween('occurrence_at', [$from, $to])
                ->orWhereBetween('event_at', [$from, $to])
            )
            ->with('reminders')
            ->get();

        // Two indexes: by canonical RRULE slot and by actual event date (for moved occurrences)
        $byOccurrenceAt = $materializedRows->keyBy(fn ($e) => "{$e->series_id}_{$e->occurrence_at?->toDateString()}");
        $byEventAt      = $materializedRows->keyBy(fn ($e) => "{$e->series_id}_{$e->event_at?->toDateString()}");

        $matched = [];

        foreach ($masters as $master) {
            try {
                $rrule       = new RRule($master->recurrence_rule, $master->event_at);
                $occurrences = $rrule->getOccurrencesBetween($from->toDateTime(), $to->toDateTime());
            } catch (\Throwable) {
                continue;
            }

            foreach ($occurrences as $dt) {
                $date   = Carbon::instance($dt)->toDateString();
                $occKey = "{$master->id}_{$date}";

                if (isset($byOccurrenceAt[$occKey])) {
                    // Normal: occurrence_at matches RRULE slot
                    $real           = $byOccurrenceAt[$occKey];
                    $matched[$occKey] = true;
                    if ($real->status === 'cancelled') continue;
                    if (! $status || $real->status === $status) $events->push($real);
                } elseif (isset($byEventAt[$occKey])) {
                    // Moved occurrence whose new event_at lands on this RRULE slot
                    $real    = $byEventAt[$occKey];
                    $realKey = "{$real->series_id}_{$real->occurrence_at?->toDateString()}";
                    $matched[$realKey] = true;
                    if ($real->status === 'cancelled') continue;
                    if (! $status || $real->status === $status) $events->push($real);
                } else {
                    if ($status && $status !== 'active') continue;
                    $events->push((object) [
                        'id'              => "v_{$master->id}_{$date}",
                        'series_id'       => $master->id,
                        'content'         => $master->content,
                        'type'            => $master->type,
                        'event_at'        => Carbon::instance($dt)->toIso8601String(),
                        'event_end'       => null,
                        'recurrence_rule' => $master->recurrence_rule,
                        '_virtual'        => true,
                    ]);
                }
            }
        }

        // Moved occurrences whose occurrence_at is outside this range but event_at is inside
        foreach ($byOccurrenceAt as $key => $occ) {
            if (isset($matched[$key])) continue;
            if ($occ->status === 'cancelled') continue;
            if ($status && $occ->status !== $status) continue;
            $events->push($occ);
        }

        $sorted = $events->sortBy(fn ($e) => is_object($e) && isset($e->_virtual)
            ? $e->event_at
            : (is_object($e) ? optional($e->event_at)->timestamp : 0)
        );

        $data = $sorted->map(function ($e) {
            if (is_object($e) && isset($e->_virtual) && $e->_virtual) {
                return [
                    'id'              => $e->id,
                    'virtual'         => true,
                    'series_id'       => $e->series_id,
                    'content'         => $e->content,
                    'type'            => $e->type,
                    'event_at'        => $e->event_at,
                    'event_end'       => $e->event_end,
                    'recurrence_rule' => $e->recurrence_rule,
                    'reminders'       => [],
                ];
            }

            return (new AssistantEventResource($e))->toArray(request());
        });

        return response()->json(['data' => $data->values()]);
    }
}
