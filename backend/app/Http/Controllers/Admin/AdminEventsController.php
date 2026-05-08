<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\AssistantEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEventsController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => 'nullable|integer',
            'email'    => 'nullable|email',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'status'   => 'nullable|string|in:active,cancelled,completed',
            'type'     => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $hasFilter = $request->filled('user_id')
            || $request->filled('email')
            || $request->filled('from')
            || $request->filled('to')
            || $request->filled('status')
            || $request->filled('type');

        if (! $hasFilter) {
            return response()->json(['data' => [], 'meta' => ['pagination' => null]]);
        }

        $events = DB::table('assistant_events AS e')
            ->join('users AS u', 'u.id', '=', 'e.user_id')
            ->whereNull('e.deleted_at')
            ->when($request->filled('user_id'), fn ($q) => $q->where('e.user_id', $request->integer('user_id')))
            ->when($request->filled('email'),   fn ($q) => $q->whereRaw('LOWER(u.email) = ?', [strtolower($request->input('email'))]))
            ->when($request->filled('from'),    fn ($q) => $q->where('e.event_at', '>=', $request->input('from')))
            ->when($request->filled('to'),      fn ($q) => $q->where('e.event_at', '<=', $request->input('to') . ' 23:59:59'))
            ->when($request->filled('status'),  fn ($q) => $q->where('e.status', $request->input('status')))
            ->when($request->filled('type'),    fn ($q) => $q->where('e.type', $request->input('type')))
            ->select(
                'e.id',
                'e.content',
                'e.type',
                'e.status',
                'e.event_at',
                'e.event_end',
                'e.recurrence_rule',
                'e.series_id',
                'e.created_at',
                'u.id AS user_id',
                'u.name AS user_name',
                'u.email AS user_email',
                DB::raw('(SELECT COUNT(*) FROM event_reminders r WHERE r.event_id = e.id) AS reminder_count'),
            )
            ->orderByDesc('e.event_at')
            ->simplePaginate($request->input('per_page', 25));

        return response()->json([
            'data' => $events->map(fn ($e) => $this->formatRow($e)),
            'meta' => ['pagination' => [
                'current_page' => $events->currentPage(),
                'has_more'     => $events->hasMorePages(),
            ]],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $event = AssistantEvent::with(['user', 'reminders'])->findOrFail($id);

        $master = $event->isOccurrence()
            ? AssistantEvent::select('id', 'content', 'recurrence_rule')->find($event->series_id)
            : null;

        return response()->json(['data' => [
            'id'               => $event->id,
            'content'          => $event->content,
            'type'             => $event->type,
            'status'           => $event->status,
            'event_at'         => $event->event_at?->toIso8601String(),
            'event_end'        => $event->event_end?->toIso8601String(),
            'recurrence_rule'  => $event->recurrence_rule,
            'series_id'        => $event->series_id,
            'occurrence_at'    => $event->occurrence_at?->toIso8601String(),
            'series_ends_at'   => $event->series_ends_at?->toIso8601String(),
            'created_at'       => $event->created_at?->toIso8601String(),
            'kind'             => $event->isMaster() ? 'master' : ($event->isOccurrence() ? 'occurrence' : 'single'),
            'user'             => [
                'id'    => $event->user->id,
                'name'  => $event->user->name,
                'email' => $event->user->email,
            ],
            'master'           => $master ? [
                'id'              => $master->id,
                'content'         => $master->content,
                'recurrence_rule' => $master->recurrence_rule,
            ] : null,
            'reminders'        => $event->reminders->map(fn ($r) => [
                'id'       => $r->id,
                'kind'     => $r->kind,
                'fire_at'  => $r->fire_at?->toIso8601String(),
                'status'   => $r->status,
                'fired_at' => $r->fired_at?->toIso8601String(),
            ])->sortBy('fire_at')->values(),
        ]]);
    }

    private function formatRow(object $e): array
    {
        return [
            'id'              => $e->id,
            'content'         => $e->content,
            'type'            => $e->type,
            'status'          => $e->status,
            'event_at'        => $e->event_at,
            'recurrence_rule' => $e->recurrence_rule,
            'series_id'       => $e->series_id,
            'kind'            => $e->recurrence_rule && ! $e->series_id ? 'master'
                               : ($e->series_id ? 'occurrence' : 'single'),
            'reminder_count'  => (int) $e->reminder_count,
            'user_id'         => $e->user_id,
            'user_name'       => $e->user_name,
            'user_email'      => $e->user_email,
            'created_at'      => $e->created_at,
        ];
    }
}
