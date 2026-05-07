<?php

use App\Domain\Assistant\Jobs\FireReminderRun;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\ReminderRun;
use App\Domain\Assistant\Models\TypeCatalog;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function createEventPayload(array $overrides = []): array
{
    return array_merge([
        'content'  => 'Test event',
        'event_at' => now()->addDays(5)->toIso8601String(),
        'type'     => 'meeting',
    ], $overrides);
}

it('creates a single event and schedules reminders via matrix', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->addDays(5)->toIso8601String(),
        ]))
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'content', 'event_at', 'reminders']]);

    expect(AssistantEvent::where('user_id', $user->id)->count())->toBe(1);

    // 5 days away → 1 ahead (-1 day) + 1 digest = 2 reminders
    expect(EventReminder::count())->toBe(2);
    expect(ReminderRun::count())->toBe(2);
});

it('creates master + first materialized occurrence when recurrence_rule is present', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO',
            'event_at'        => now()->addDays(5)->toIso8601String(),
        ]))
        ->assertCreated();

    expect(AssistantEvent::where('user_id', $user->id)->count())->toBe(2);

    $master = AssistantEvent::where('user_id', $user->id)->whereNotNull('recurrence_rule')->first();
    expect($master)->not->toBeNull();
    expect($master->series_id)->toBeNull();

    $occurrence = AssistantEvent::where('series_id', $master->id)->first();
    expect($occurrence)->not->toBeNull();
});

it('does not store reminders_template_json', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'recurrence_rule' => 'FREQ=DAILY',
            'event_at'        => now()->addDays(5)->toIso8601String(),
        ]))
        ->assertCreated();

    $master = AssistantEvent::whereNotNull('recurrence_rule')->where('user_id', $user->id)->firstOrFail();
    expect($master->toArray())->not->toHaveKey('reminders_template_json');
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

    $this->withHeaders(asstHdr($token, $enterprise))
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

it('dispatches FireReminderRun jobs for scheduled runs', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->addDays(5)->toIso8601String(),
        ]))
        ->assertCreated();

    Queue::assertPushed(FireReminderRun::class);
});

it('schedules inline reminders for same-day events', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->addHours(3)->toIso8601String(),
        ]))
        ->assertCreated();

    $reminders = EventReminder::all();
    expect($reminders->pluck('kind')->unique()->values()->all())->toBe(['inline']);
});

it('schedules a single inline reminder at event_at for imminent events (< 10 min)', function () {
    [$user, $enterprise, $token] = asstCtx();

    $eventAt = now()->addMinutes(5);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => $eventAt->toIso8601String(),
        ]))
        ->assertCreated();

    $reminders = EventReminder::all();
    expect($reminders->count())->toBe(1);
    expect($reminders->first()->kind)->toBe('inline');
    expect($reminders->first()->fire_at->toDateTimeString())->toBe($eventAt->toDateTimeString());
});

it('does not create reminders for past events', function () {
    [$user, $enterprise, $token] = asstCtx();

    // event_at en el pasado → scheduleForEvent genera 0 reminders
    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->subHour()->toIso8601String(),
        ]))
        ->assertCreated();

    expect(EventReminder::count())->toBe(0);
    expect(ReminderRun::count())->toBe(0);
});

it('schedules digest + ahead reminders for future events', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->addDays(5)->toIso8601String(),
        ]))
        ->assertCreated();

    $kinds = EventReminder::pluck('kind')->sort()->values()->all();
    expect($kinds)->toContain('ahead');
    expect($kinds)->toContain('digest');
});

it('schedules more ahead reminders for events far in the future', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload([
            'event_at' => now()->addYear()->addDays(10)->toIso8601String(),
        ]))
        ->assertCreated();

    // ≥1 year: 3 ahead (-1M, -1W, -1D) + 1 digest = 4
    expect(EventReminder::count())->toBe(4);
});

it('groups reminders on the same day into a shared ReminderRun', function () {
    [$user, $enterprise, $token] = asstCtx();

    $eventAt = now()->addDays(5)->toIso8601String();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload(['event_at' => $eventAt]))
        ->assertCreated();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', createEventPayload(['event_at' => $eventAt, 'content' => 'Second event']))
        ->assertCreated();

    // Both digest reminders share the same ReminderRun at 6am
    $digestRuns = ReminderRun::where('kind', 'digest')->get();
    expect($digestRuns->count())->toBe(1);
    expect($digestRuns->first()->reminders()->count())->toBe(2);
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
