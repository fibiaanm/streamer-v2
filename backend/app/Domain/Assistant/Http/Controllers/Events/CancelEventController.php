<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Support\EventResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CancelEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'series' => 'boolean',
        ]);

        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $series   = $validated['series'] ?? false;
        $userId   = $request->user()->id;
        $resolved = EventResolver::resolve($eventId, $userId);

        if ($series) {
            $master = $resolved->isVirtual()
                ? $resolved->master()
                : ($resolved->model()->master ?? $resolved->model());

            $master->update(['status' => 'cancelled']);

            AssistantEvent::where('series_id', $master->id)
                ->where('event_at', '>', now())
                ->delete();

            $master->reminders()->where('status', 'pending')->delete();

            return response()->json(['data' => new AssistantEventResource($master)]);
        }

        if ($resolved->isVirtual()) {
            $model = $resolved->materialize(['status' => 'cancelled']);
        } else {
            $model = $resolved->model();
            $model->update(['status' => 'cancelled']);
            $model->reminders()->where('status', 'pending')->delete();
        }

        return response()->json(['data' => new AssistantEventResource($model)]);
    }
}
