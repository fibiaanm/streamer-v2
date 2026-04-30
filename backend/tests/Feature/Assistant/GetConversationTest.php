<?php

use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;

it('creates conversation on first call and returns null active_session_id when no session exists', function () {
    [$user, $enterprise, $token] = asstCtx();

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'active_session_id', 'created_at']]);

    expect($response->json('data.active_session_id'))->toBeNull();
    expect(Conversation::where('user_id', $user->id)->count())->toBe(1);
    expect(AssistantSession::count())->toBe(0);
});

it('returns the same conversation on subsequent calls', function () {
    [$user, $enterprise, $token] = asstCtx();
    $headers = asstHdr($token, $enterprise);

    $first  = $this->withHeaders($headers)->getJson('/api/v1/assistant/conversation')->json('data.id');
    $second = $this->withHeaders($headers)->getJson('/api/v1/assistant/conversation')->json('data.id');

    expect($first)->toBe($second);
    expect(Conversation::where('user_id', $user->id)->count())->toBe(1);
});

it('returns active_session_id when session is within 24 hours', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create([
        'conversation_id' => $conversation->id,
        'last_message_at' => now()->subHours(10),
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertOk();

    expect($response->json('data.active_session_id'))->toBe($session->getHashId());
    expect(AssistantSession::count())->toBe(1);
});

it('returns null active_session_id when last session is over 24 hours old', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->inactive()->create(['conversation_id' => $conversation->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertOk();

    expect($response->json('data.active_session_id'))->toBeNull();
    expect(AssistantSession::count())->toBe(1);
});

it('does not create a session when no active session exists', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertOk();

    expect(AssistantSession::count())->toBe(0);
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/assistant/conversation')->assertUnauthorized();
});
