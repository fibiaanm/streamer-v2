<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Models\User;

// ── Helpers ───────────────────────────────────────────────────────────────────

function eventsUrl(array $params = []): string
{
    $query = $params ? '?' . http_build_query($params) : '';
    return "/api/v1/assistant/events{$query}";
}

// ── Tests ─────────────────────────────────────────────────────────────────────

it('returns single events in the requested range', function () {
    [$user, $enterprise, $token] = asstCtx();

    AssistantEvent::factory()->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);
    AssistantEvent::factory()->create([
        'user_id'  => $user->id,
        'event_at' => '2026-07-01 10:00:00', // outside range
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.virtual'))->toBeFalse();
});

it('returns materialized occurrences in the requested range', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    AssistantEvent::factory()->occurrence($master)->create([
        'event_at'      => '2026-05-04 10:00:00',
        'occurrence_at' => '2026-05-04 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->not->toContain("v_{$master->id}_2026-05-04"); // fila real existe, no virtual
});

it('returns virtual occurrences for dates without a real row', function () {
    [$user, $enterprise, $token] = asstCtx();

    AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    $virtuals = collect($response->json('data'))->filter(fn ($e) => $e['virtual']);
    expect($virtuals->count())->toBeGreaterThan(0);
    expect($virtuals->first()['id'])->toStartWith('v_');
});

it('substitutes virtual occurrence with real row when exception exists', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-05 10:00:00', // moved
        'occurrence_at' => '2026-05-04 10:00:00',
        'content'       => 'Updated content',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    // must not appear as virtual for the original slot
    $ids = collect($response->json('data'))->pluck('id')->all();
    foreach ($ids as $id) {
        expect($id)->not->toBe("v_{$master->id}_2026-05-04");
    }
});

it('omits virtual occurrences whose exception is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=WEEKLY;BYDAY=MO')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00',
    ]);

    AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-04 10:00:00',
        'occurrence_at' => '2026-05-04 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    foreach ($ids as $id) {
        expect($id)->not->toBe("v_{$master->id}_2026-05-04");
    }
});

it('excludes cancelled events by default', function () {
    [$user, $enterprise, $token] = asstCtx();

    AssistantEvent::factory()->cancelled()->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('includes cancelled events when status=cancelled is requested', function () {
    [$user, $enterprise, $token] = asstCtx();

    AssistantEvent::factory()->cancelled()->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31', 'status' => 'cancelled']))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('does not return events from another user', function () {
    [$user, $enterprise, $token] = asstCtx();
    $other = User::factory()->create();

    AssistantEvent::factory()->create([
        'user_id'  => $other->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson(eventsUrl(['from' => '2026-05-01', 'to' => '2026-05-31']))
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});
