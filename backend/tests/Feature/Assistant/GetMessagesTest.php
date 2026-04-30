<?php

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Models\User;

it('returns messages for the given session paginated in ascending order', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    AssistantMessage::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'session_id'      => $session->id,
        'role'            => 'user',
    ]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages?session=' . $session->getHashId())
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);

    expect($response->json('data'))->toHaveCount(3);

    $dates = collect($response->json('data'))->pluck('created_at');
    expect($dates->values()->all())->toBe($dates->sort()->values()->all());
});

it('requires session query parameter', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages')
        ->assertStatus(422);
});

it('returns 404 for a session belonging to another user', function () {
    [$user, $enterprise, $token] = asstCtx();
    $otherUser                   = User::factory()->create();

    $otherConversation = Conversation::factory()->create(['user_id' => $otherUser->id]);
    $otherSession      = AssistantSession::factory()->create(['conversation_id' => $otherConversation->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages?session=' . $otherSession->getHashId())
        ->assertNotFound();
});

it('returns 404 for a non-existent session hash', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages?session=nonexistent')
        ->assertNotFound();
});

it('excludes tool_call and tool_result messages', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);
    $base         = ['conversation_id' => $conversation->id, 'session_id' => $session->id];

    AssistantMessage::factory()->create($base + ['role' => 'user']);
    AssistantMessage::factory()->create($base + ['role' => 'assistant']);
    AssistantMessage::factory()->toolCall()->create($base);
    AssistantMessage::factory()->toolResult()->create($base);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages?session=' . $session->getHashId())
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    $roles = collect($response->json('data'))->pluck('role');
    expect($roles->contains('tool_call'))->toBeFalse();
    expect($roles->contains('tool_result'))->toBeFalse();
});

it('returns empty data when no messages exist for the session', function () {
    [$user, $enterprise, $token] = asstCtx();

    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation/messages?session=' . $session->getHashId())
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/assistant/conversation/messages?session=abc')->assertUnauthorized();
});
