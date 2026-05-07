<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('snoozes a real recurring occurrence to the next slot after until', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);
    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'event_at'      => '2026-05-04 10:00:00',
        'occurrence_at' => '2026-05-04 10:00:00',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/snooze", [
            'until' => '2026-05-10T00:00:00Z',
        ])
        ->assertOk();

    expect($occurrence->fresh()->status)->toBe('cancelled');

    $next = AssistantEvent::where('series_id', $master->id)
        ->where('status', 'active')
        ->first();
    expect($next)->not->toBeNull();
    expect($next->event_at->toDateString())->toBe('2026-05-11');
});

it('schedules reminders from the matrix on the snoozed occurrence', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);
    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'event_at'      => '2026-05-04 10:00:00',
        'occurrence_at' => '2026-05-04 10:00:00',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/snooze", [
            'until' => '2026-05-10T00:00:00Z',
        ])
        ->assertOk();

    $next = AssistantEvent::where('series_id', $master->id)->where('status', 'active')->first();
    // 2026-05-11 is 5 days from now (2026-05-06) → 1 ahead (-1 day) + 1 digest
    expect(EventReminder::where('event_id', $next->id)->count())->toBe(2);
});
