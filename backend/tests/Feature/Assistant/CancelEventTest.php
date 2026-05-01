<?php

use App\Domain\Assistant\Models\AssistantEvent;

it('cancels a real event with series=false', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$event->getHashId()}/cancel", ['series' => false])
        ->assertOk();

    expect($event->fresh()->status)->toBe('cancelled');
});

it('materializes a cancelled exception for a virtual event with series=false', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    $virtualId = "v_{$master->id}_2026-05-04";

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$virtualId}/cancel", ['series' => false])
        ->assertOk();

    $exception = AssistantEvent::where('series_id', $master->id)
        ->whereDate('occurrence_at', '2026-05-04')
        ->first();
    expect($exception)->not->toBeNull();
    expect($exception->status)->toBe('cancelled');
});

it('cancels the entire series with series=true from a real occurrence', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master     = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);
    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'event_at' => now()->addDay(),
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/cancel", ['series' => true])
        ->assertOk();

    expect($master->fresh()->status)->toBe('cancelled');
    expect(AssistantEvent::where('series_id', $master->id)->where('event_at', '>', now())->count())->toBe(0);
});

it('cancels the entire series with series=true from a virtual event', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master    = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);
    $virtualId = "v_{$master->id}_2026-05-04";

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$virtualId}/cancel", ['series' => true])
        ->assertOk();

    expect($master->fresh()->status)->toBe('cancelled');
});
