<?php

namespace App\Domain\Assistant\Http\Controllers\Events;

use App\Domain\Assistant\Http\Resources\AssistantEventResource;
use App\Domain\Assistant\Jobs\FireEventReminder;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\TypeCatalog;
use App\Domain\Assistant\Support\MorphTypeMap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Throwable;

class CreateEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'                  => 'required|string',
            'event_at'                 => 'required|date',
            'event_end'                => 'nullable|date|after:event_at',
            'type'                     => 'required|string',
            'recurrence_rule'          => 'nullable|string',
            'reminders'                => 'required|array',
            'reminders.*.offset'       => 'required|string',
            'reminders.*.message'      => 'required|string',
            'referenceable'            => 'nullable|array',
            'referenceable.type'       => 'required_with:referenceable|string',
            'referenceable.id'         => 'required_with:referenceable|string',
        ]);

        $user    = $request->user();
        $eventAt = Carbon::parse($validated['event_at']);

        $this->ensureTypeCatalog($user->id, $validated['type']);

        [$referenceableType, $referenceableId] = $this->resolveReferenceable(
            $validated['referenceable'] ?? null,
            $user->id,
        );

        if (! empty($validated['recurrence_rule'])) {
            $event = $this->createRecurring($user->id, $validated, $eventAt, $referenceableType, $referenceableId);
        } else {
            $event = $this->createSingle($user->id, $validated, $eventAt, $referenceableType, $referenceableId);
        }

        $event->load('reminders');

        return (new AssistantEventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    private function createSingle(int $userId, array $data, Carbon $eventAt, ?string $type, mixed $id): AssistantEvent
    {
        $template = collect($data['reminders'])->map(fn ($r) => [
            'offset'  => $r['offset'],
            'message' => $r['message'],
        ])->all();

        $event = AssistantEvent::create([
            'user_id'                 => $userId,
            'content'                 => $data['content'],
            'event_at'                => $eventAt,
            'event_end'               => isset($data['event_end']) ? Carbon::parse($data['event_end']) : null,
            'type'                    => $data['type'],
            'status'                  => 'active',
            'reminders_template_json' => $template ?: null,
            'referenceable_type'      => $type,
            'referenceable_id'        => $id,
        ]);

        $this->createReminders($event, $data['reminders'], $eventAt);

        return $event;
    }

    private function createRecurring(int $userId, array $data, Carbon $eventAt, ?string $type, mixed $id): AssistantEvent
    {
        $template = collect($data['reminders'])->map(fn ($r) => [
            'offset'  => $r['offset'],
            'message' => $r['message'],
        ])->all();

        $master = AssistantEvent::create([
            'user_id'                 => $userId,
            'content'                 => $data['content'],
            'event_at'                => $eventAt,
            'event_end'               => isset($data['event_end']) ? Carbon::parse($data['event_end']) : null,
            'type'                    => $data['type'],
            'recurrence_rule'         => $data['recurrence_rule'],
            'reminders_template_json' => $template,
            'status'                  => 'active',
            'referenceable_type'      => $type,
            'referenceable_id'        => $id,
        ]);

        $occurrence = AssistantEvent::create([
            'user_id'       => $userId,
            'series_id'     => $master->id,
            'occurrence_at' => $eventAt,
            'content'       => $data['content'],
            'event_at'      => $eventAt,
            'event_end'     => isset($data['event_end']) ? Carbon::parse($data['event_end']) : null,
            'type'          => $data['type'],
            'status'        => 'active',
        ]);

        $this->createReminders($occurrence, $data['reminders'], $eventAt);

        return $occurrence;
    }

    private function createReminders(AssistantEvent $event, array $reminders, Carbon $eventAt): void
    {
        foreach ($reminders as $r) {
            $fireAt = $this->parseOffset($r['offset'], $eventAt);

            $reminder = EventReminder::create([
                'event_id' => $event->id,
                'fire_at'  => $fireAt,
                'message'  => $r['message'],
                'status'   => 'pending',
            ]);

            if ($fireAt->isFuture()) {
                FireEventReminder::dispatch($reminder->id)->delay($fireAt);
            }
        }
    }

    private function parseOffset(string $offset, Carbon $base): Carbon
    {
        if ($offset === '0') {
            return $base->copy();
        }

        return $base->copy()->modify($offset);
    }

    private function ensureTypeCatalog(int $userId, string $type): void
    {
        TypeCatalog::firstOrCreate(
            ['user_id' => $userId, 'domain' => 'event', 'name' => $type],
        );
    }

    private function resolveReferenceable(?array $ref, int $userId): array
    {
        if (! $ref) {
            return [null, null];
        }

        try {
            $class = MorphTypeMap::toClass($ref['type']);
        } catch (InvalidArgumentException) {
            abort(422, "Invalid referenceable type: {$ref['type']}");
        }

        // Resolve via hash ID (HasHashId::resolveRouteBinding decodes the hash)
        $model = null;
        $instance = new $class;
        if (method_exists($instance, 'resolveRouteBinding')) {
            $model = (new $class)->resolveRouteBinding($ref['id']);
        }

        if (! $model) {
            $model = $class::find($ref['id']);
        }

        if (! $model) {
            abort(403, 'Referenceable not found.');
        }

        if ($model->user_id !== $userId) {
            abort(403, 'Referenceable does not belong to the user.');
        }

        return [$class, $model->id];
    }
}
