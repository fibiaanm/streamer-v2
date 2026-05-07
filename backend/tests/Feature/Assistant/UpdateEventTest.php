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

    $event = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => '2026-06-01 10:00:00']);
    $run   = \App\Domain\Assistant\Models\ReminderRun::create([
        'user_id' => $user->id,
        'run_at'  => '2026-05-31 10:00:00',
        'kind'    => 'ahead',
        'status'  => 'pending',
    ]);
    $reminder = EventReminder::factory()->create([
        'event_id'        => $event->id,
        'fire_at'         => '2026-05-31 10:00:00',
        'status'          => 'pending',
        'reminder_run_id' => $run->id,
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", ['event_at' => '2026-07-01T10:00:00Z'])
        ->assertOk();

    // old reminder released: reminder_run_id nulled, status cancelled
    expect($reminder->fresh()->reminder_run_id)->toBeNull();
    expect($reminder->fresh()->status)->toBe('cancelled');

    // new reminders scheduled for the new event_at
    expect(EventReminder::where('event_id', $event->id)->where('status', 'pending')->count())->toBeGreaterThan(0);
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
