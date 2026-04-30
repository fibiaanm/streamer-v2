<?php

use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;

it('returns sessions for the authenticated user in descending order', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AssistantSession::factory()->create([
        'conversation_id' => $conversation->id,
        'started_at'      => now()->subDays(3),
        'last_message_at' => now()->subDays(3),
    ]);
    AssistantSession::factory()->create([
        'conversation_id' => $conversation->id,
        'started_at'      => now()->subDay(),
        'last_message_at' => now()->subDay(),
    ]);
    AssistantSession::factory()->create([
        'conversation_id' => $conversation->id,
        'started_at'      => now()->subHours(2),
        'last_message_at' => now()->subHours(2),
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['next_cursor', 'prev_cursor']]);

    expect($response->json('data'))->toHaveCount(3);

    $dates = collect($response->json('data'))->pluck('started_at');
    expect($dates->values()->all())->toBe($dates->sortDesc()->values()->all());
});

it('returns session resource with expected fields', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk();

    $session = $response->json('data.0');
    expect($session)->toHaveKeys(['id', 'title', 'is_active', 'started_at', 'last_message_at']);
    expect($session['title'])->toBe('Untitled');
});

it('marks session as active when last_message_at is within 24 hours', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->create([
        'conversation_id' => $conversation->id,
        'last_message_at' => now()->subHours(2),
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk();

    expect($response->json('data.0.is_active'))->toBeTrue();
});

it('marks session as inactive when last_message_at is over 24 hours ago', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->inactive()->create(['conversation_id' => $conversation->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk();

    expect($response->json('data.0.is_active'))->toBeFalse();
});

it('returns empty list when user has no sessions', function () {
    [$user, $enterprise, $token] = asstCtx();

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('does not return sessions from another user', function () {
    [$user, $enterprise, $token] = asstCtx();

    $otherConv = Conversation::factory()->create();
    AssistantSession::factory()->count(3)->create(['conversation_id' => $otherConv->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/sessions')
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/assistant/sessions')->assertUnauthorized();
});
