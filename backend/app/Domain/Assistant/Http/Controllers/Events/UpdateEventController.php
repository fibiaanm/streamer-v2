<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Jobs\FireEventReminder;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Support\EventResolver;
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
        $resolved = EventResolver::resolve($eventId, $userId);

        if ($resolved->isVirtual()) {
            $overrides = array_filter($validated, fn ($v) => $v !== null);
            $model     = $resolved->materialize($overrides);
        } else {
            $model = $resolved->model();

            $oldEventAt = $model->event_at?->copy();
            $model->fill(array_filter($validated, fn ($v) => $v !== null));
            $model->save();

            if (isset($validated['event_at']) && $model->event_at != $oldEventAt) {
                $this->recalculateReminders($model, Carbon::parse($validated['event_at']));
            }
        }

        $model->load('reminders');

        return (new AssistantEventResource($model))->response()->setStatusCode(200);
    }

    private function recalculateReminders(\App\Domain\Assistant\Models\AssistantEvent $event, Carbon $newEventAt): void
    {
        $event->reminders()->where('status', 'pending')->delete();

        $master   = $event->master;
        $template = $master?->reminders_template_json ?? $event->reminders_template_json;

        if (! $template) {
            return;
        }

        foreach ($template as $tpl) {
            $offset = $tpl['offset'];
            $fireAt = $offset === '0' ? $newEventAt->copy() : $newEventAt->copy()->modify($offset);

            $reminder = EventReminder::create([
                'event_id' => $event->id,
                'fire_at'  => $fireAt,
                'message'  => $tpl['message'],
                'status'   => 'pending',
            ]);

            if ($fireAt->isFuture()) {
                FireEventReminder::dispatch($reminder->id)->delay($fireAt);
            }
        }
    }
}
