<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Support\EventResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DetachEventReferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $eventId  = (string) ($request->route('event') ?? $request->route('eventId'));
        $userId   = $request->user()->id;
        $resolved = EventResolver::resolve($eventId, $userId);
        $event    = $resolved->model();

        $event->update([
            'referenceable_type' => null,
            'referenceable_id'   => null,
        ]);

        $event->load('reminders');

        return (new AssistantEventResource($event))->response();
    }
}
