<?php

use App\Domain\Assistant\Models\AssistantEvent;

function seriesCtx(): array
{
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY')->create([
        'user_id' => $user->id,
        'content' => 'Original content',
        'type'    => 'meeting',
    ]);

    $futureOccurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'content'       => 'Original content',
        'type'          => 'meeting',
        'event_at'      => now()->addWeek(),
        'occurrence_at' => now()->addWeek(),
    ]);

    $pastOccurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'content'       => 'Original content',
        'type'          => 'meeting',
        'event_at'      => now()->subWeek(),
        'occurrence_at' => now()->subWeek(),
    ]);

    return [$user, $enterprise, $token, $master, $futureOccurrence, $pastOccurrence];
}

it('updates content on master and all future active occurrences', function () {
    [$user, $enterprise, $token, $master, $future] = seriesCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'Updated content',
        ])
        ->assertOk();

    expect($master->fresh()->content)->toBe('Updated content');
    expect($future->fresh()->content)->toBe('Updated content');
});

it('does not touch past occurrences', function () {
    [$user, $enterprise, $token, $master, , $past] = seriesCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'New content',
        ])
        ->assertOk();

    expect($past->fresh()->content)->toBe('Original content');
});

it('does not touch cancelled future occurrences', function () {
    [$user, $enterprise, $token, $master] = seriesCtx();

    $cancelled = AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'user_id'       => $master->user_id,
        'content'       => 'Original content',
        'event_at'      => now()->addDays(3),
        'occurrence_at' => now()->addDays(3),
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'New content',
        ])
        ->assertOk();

    expect($cancelled->fresh()->content)->toBe('Original content');
});

it('works with an occurrence ID instead of master ID', function () {
    [$user, $enterprise, $token, $master, $future] = seriesCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$future->getHashId()}/series", [
            'content' => 'Via occurrence',
        ])
        ->assertOk();

    expect($master->fresh()->content)->toBe('Via occurrence');
    expect($future->fresh()->content)->toBe('Via occurrence');
});

it('works with a virtual occurrence ID', function () {
    [$user, $enterprise, $token, $master] = seriesCtx();

    $virtualId = "v_{$master->id}_2026-06-01";

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$virtualId}/series", [
            'type' => 'yoga',
        ])
        ->assertOk();

    expect($master->fresh()->type)->toBe('yoga');
});

it('returns 422 when no fields are provided', function () {
    [$user, $enterprise, $token, $master] = seriesCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [])
        ->assertUnprocessable();
});

it('returns 422 when applied to a single (non-series) event', function () {
    [$user, $enterprise, $token] = asstCtx();

    $single = AssistantEvent::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$single->getHashId()}/series", [
            'content' => 'New content',
        ])
        ->assertUnprocessable();
});

it('does not allow updating another user series', function () {
    [, , , $master] = seriesCtx();
    [$user2, $enterprise2, $token2] = asstCtx();

    $this->withHeaders(asstHdr($token2, $enterprise2))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'Hacked',
        ])
        ->assertNotFound();
});

it('updates the time on master and all future active occurrences', function () {
    [$user, $enterprise, $token, $master, $future] = seriesCtx();

    // Set master event_at to a known time so we can verify the change
    $master->update(['event_at' => now()->addMonth()->setTime(9, 0, 0)->utc()]);
    $future->update(['event_at' => now()->addWeek()->setTime(9, 0, 0)->utc()]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'time' => '14:30',
        ])
        ->assertOk();

    $masterFresh  = $master->fresh();
    $futureFresh  = $future->fresh();
    $userTimezone = $user->timezone ?? 'UTC';

    expect($masterFresh->event_at->setTimezone($userTimezone)->format('H:i'))->toBe('14:30');
    expect($futureFresh->event_at->setTimezone($userTimezone)->format('H:i'))->toBe('14:30');
});

it('returns empty exceptions array when no occurrences were rescheduled', function () {
    [$user, $enterprise, $token, $master] = seriesCtx();

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'New content',
        ])
        ->assertOk();

    expect($response->json('exceptions'))->toBeEmpty();
});

it('reports rescheduled occurrences in exceptions without modifying them', function () {
    [$user, $enterprise, $token, $master] = seriesCtx();

    // A rescheduled occurrence: event_at ≠ occurrence_at (moved to a different date)
    $originalSlot = now()->addWeek()->setTime(10, 0, 0)->utc();
    $movedTo      = now()->addWeek()->addDay()->setTime(10, 0, 0)->utc();

    $rescheduled = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'content'       => 'Original content',
        'type'          => 'meeting',
        'event_at'      => $movedTo,
        'occurrence_at' => $originalSlot,
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'New content',
        ])
        ->assertOk();

    // Exception must be reported
    $exceptions = $response->json('exceptions');
    expect($exceptions)->toHaveCount(1);
    expect($exceptions[0]['id'])->toBe($rescheduled->getHashId());

    // Content must NOT have been updated on the rescheduled occurrence
    expect($rescheduled->fresh()->content)->toBe('Original content');
});

it('updates normal occurrences but leaves rescheduled ones untouched', function () {
    [$user, $enterprise, $token, $master, $normalFuture] = seriesCtx();

    $originalSlot = now()->addDays(3)->setTime(10, 0, 0)->utc();
    $movedTo      = now()->addDays(4)->setTime(10, 0, 0)->utc();

    $rescheduled = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'content'       => 'Rescheduled content',
        'event_at'      => $movedTo,
        'occurrence_at' => $originalSlot,
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$master->getHashId()}/series", [
            'content' => 'Bulk update',
        ])
        ->assertOk();

    // Normal occurrence updated
    expect($normalFuture->fresh()->content)->toBe('Bulk update');

    // Rescheduled occurrence NOT updated
    expect($rescheduled->fresh()->content)->toBe('Rescheduled content');

    // Exception reported
    expect($response->json('exceptions'))->toHaveCount(1);
});
