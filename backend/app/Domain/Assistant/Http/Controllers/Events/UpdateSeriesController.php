<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Support\EventResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UpdateSeriesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'   => 'nullable|string',
            'type'      => 'nullable|string',
            'event_end' => 'nullable|date',
        ]);

        $fields = array_filter($validated, fn ($v) => $v !== null);

        if (empty($fields)) {
            abort(422, 'At least one field must be provided.');
        }

        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $userId   = $request->user()->id;
        $resolved = EventResolver::resolve($eventId, $userId);

        $master = $this->resolveMaster($resolved);

        if (isset($fields['event_end'])) {
            $fields['event_end'] = Carbon::parse($fields['event_end'])->toDateTimeString();
        }

        $master->fill($fields)->save();

        AssistantEvent::where('series_id', $master->id)
            ->where('event_at', '>', now())
            ->where('status', 'active')
            ->update($fields);

        return (new AssistantEventResource($master))->response()->setStatusCode(200);
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
