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
