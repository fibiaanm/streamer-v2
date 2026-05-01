<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;

it('updates a real event by id', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id, 'content' => 'Old content']);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", ['content' => 'New content'])
        ->assertOk()
        ->assertJsonPath('data.content', 'New content');

    expect($event->fresh()->content)->toBe('New content');
});

it('recalculates pending reminders when event_at changes on a real event', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event    = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => '2026-06-01 10:00:00']);
    $reminder = EventReminder::factory()->create([
        'event_id' => $event->id,
        'fire_at'  => '2026-05-31 10:00:00',
        'status'   => 'pending',
        'message'  => 'Reminder',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", ['event_at' => '2026-07-01T10:00:00Z'])
        ->assertOk();

    expect(EventReminder::find($reminder->id))->toBeNull();
});

it('materializes a virtual event as exception with changes', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    $virtualId = "v_{$master->id}_2026-05-04";

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$virtualId}", ['content' => 'Exception content'])
        ->assertOk();

    expect($response->json('data.virtual'))->toBeFalse();
    expect($response->json('data.content'))->toBe('Exception content');

    $exception = AssistantEvent::where('series_id', $master->id)
        ->whereDate('occurrence_at', '2026-05-04')
        ->first();
    expect($exception)->not->toBeNull();
    expect($exception->content)->toBe('Exception content');
});
