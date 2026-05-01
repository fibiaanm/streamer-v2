<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\TypeCatalog;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function createEventPayload(array $overrides = []): array
{
    return array_merge([
        'content'   => 'Test event',
        'event_at'  => '2026-06-01T10:00:00Z',
        'type'      => 'meeting',
        'reminders' => [
            ['offset' => '-1 day', 'message' => 'Tomorrow is the meeting'],
        ],
    ], $overrides);
}

it('creates a single event with reminders and correct fire_at', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'content', 'event_at', 'reminders']]);

    expect(AssistantEvent::where('user_id', $user->id)->count())->toBe(1);
    expect(EventReminder::count())->toBe(1);

    $reminder = EventReminder::first();
    expect($reminder->fire_at->toDateString())->toBe('2026-05-31'); // -1 day from 2026-06-01
});

it('creates master + first materialized occurrence when recurrence_rule is present', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO',
        ]))
        ->assertCreated();

    expect(AssistantEvent::where('user_id', $user->id)->count())->toBe(2);

    $master = AssistantEvent::where('user_id', $user->id)->whereNotNull('recurrence_rule')->first();
    expect($master)->not->toBeNull();
    expect($master->series_id)->toBeNull();

    $occurrence = AssistantEvent::where('series_id', $master->id)->first();
    expect($occurrence)->not->toBeNull();
    expect($occurrence->occurrence_at)->not->toBeNull();
});

it('saves reminders_template_json in the master', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'recurrence_rule' => 'FREQ=DAILY',
            'reminders'       => [
                ['offset' => '-1 day', 'message' => 'Tomorrow'],
                ['offset' => '-7 days', 'message' => 'Next week'],
            ],
        ]))
        ->assertCreated();

    $master = AssistantEvent::whereNotNull('recurrence_rule')->where('user_id', $user->id)->firstOrFail();
    expect($master->reminders_template_json)->toHaveCount(2);
    expect($master->reminders_template_json[0]['offset'])->toBe('-1 day');
});

it('auto-creates TypeCatalog when type does not exist for user', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload(['type' => 'new_custom_type']))
        ->assertCreated();

    expect(TypeCatalog::where('user_id', $user->id)->where('name', 'new_custom_type')->exists())->toBeTrue();
});

it('assigns referenceable correctly', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'referenceable' => ['type' => 'list', 'id' => $list->getHashId()],
        ]))
        ->assertCreated();

    $event = AssistantEvent::where('user_id', $user->id)->first();
    expect($event->referenceable_type)->toBe(AssistantList::class);
    expect((int) $event->referenceable_id)->toBe($list->id);
});

it('returns 422 for invalid referenceable type alias', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'referenceable' => ['type' => 'invalid_type', 'id' => '123'],
        ]))
        ->assertUnprocessable();
});

it('returns 403 when referenceable does not belong to user', function () {
    [$user, $enterprise, $token] = asstCtx();

    $other = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'referenceable' => ['type' => 'list', 'id' => $list->getHashId()],
        ]))
        ->assertForbidden();
});
