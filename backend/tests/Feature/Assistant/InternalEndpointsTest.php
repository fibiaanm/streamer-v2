<?php

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Memory;
use App\Models\User;

function serviceHdr(): array
{
    return ['Authorization' => 'Bearer ' . config('assistant.service_token')];
}

// ── Auth ──────────────────────────────────────────────────────────────────────

it('rejects unprocessed-messages without service token', function () {
    $user = User::factory()->create();
    $this->getJson("/api/v1/internal/unprocessed-messages/{$user->getHashId()}")
        ->assertUnauthorized();
});

it('rejects memories without service token', function () {
    $user = User::factory()->create();
    $this->getJson("/api/v1/internal/memories/{$user->getHashId()}")
        ->assertUnauthorized();
});

it('rejects memory upsert without service token', function () {
    $user = User::factory()->create();
    $this->putJson("/api/v1/internal/memories/{$user->getHashId()}/preferences", [])
        ->assertUnauthorized();
});

it('rejects mark-processed without service token', function () {
    $this->postJson('/api/v1/internal/mark-processed', [])
        ->assertUnauthorized();
});

// ── GET unprocessed-messages ──────────────────────────────────────────────────

it('returns unprocessed messages for the given user', function () {
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    AssistantMessage::factory()->count(3)->create([
        'conversation_id'  => $conversation->id,
        'session_id'       => $session->id,
        'memory_processed' => false,
    ]);
    AssistantMessage::factory()->create([
        'conversation_id'  => $conversation->id,
        'session_id'       => $session->id,
        'memory_processed' => true,
    ]);

    $this->withHeaders(serviceHdr())
        ->getJson("/api/v1/internal/unprocessed-messages/{$user->getHashId()}")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('does not return processed messages', function () {
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    AssistantMessage::factory()->count(5)->create([
        'conversation_id'  => $conversation->id,
        'session_id'       => $session->id,
        'memory_processed' => true,
    ]);

    $this->withHeaders(serviceHdr())
        ->getJson("/api/v1/internal/unprocessed-messages/{$user->getHashId()}")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ── GET memories ──────────────────────────────────────────────────────────────

it('returns all memories for the given user', function () {
    $user = User::factory()->create();

    Memory::factory()->count(3)->create(['user_id' => $user->id]);

    $this->withHeaders(serviceHdr())
        ->getJson("/api/v1/internal/memories/{$user->getHashId()}")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('does not return memories from other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Memory::factory()->count(2)->create(['user_id' => $other->id]);

    $this->withHeaders(serviceHdr())
        ->getJson("/api/v1/internal/memories/{$user->getHashId()}")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ── PUT memories/{category} ───────────────────────────────────────────────────

it('creates a new memory when category does not exist', function () {
    $user = User::factory()->create();

    $this->withHeaders(serviceHdr())
        ->putJson("/api/v1/internal/memories/{$user->getHashId()}/preferences", [
            'description' => 'User prefers dark mode.',
            'content'     => 'Prefers dark mode. Uses keyboard shortcuts.',
        ])
        ->assertOk();

    expect(Memory::where('user_id', $user->id)->where('category', 'preferences')->count())->toBe(1);
});

it('updates an existing memory when category already exists', function () {
    $user = User::factory()->create();
    Memory::factory()->create(['user_id' => $user->id, 'category' => 'preferences', 'content' => 'old content']);

    $this->withHeaders(serviceHdr())
        ->putJson("/api/v1/internal/memories/{$user->getHashId()}/preferences", [
            'description' => 'Updated.',
            'content'     => 'new content',
        ])
        ->assertOk();

    expect(Memory::where('user_id', $user->id)->where('category', 'preferences')->count())->toBe(1);
    expect(Memory::where('user_id', $user->id)->where('category', 'preferences')->value('content'))->toBe('new content');
});

it('requires description and content to upsert a memory', function () {
    $user = User::factory()->create();

    $this->withHeaders(serviceHdr())
        ->putJson("/api/v1/internal/memories/{$user->getHashId()}/preferences", [])
        ->assertStatus(422);
});

// ── POST mark-processed ───────────────────────────────────────────────────────

it('marks the given messages as memory processed', function () {
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    $messages = AssistantMessage::factory()->count(3)->create([
        'conversation_id'  => $conversation->id,
        'session_id'       => $session->id,
        'memory_processed' => false,
    ]);

    $ids = $messages->map(fn ($m) => $m->getHashId())->all();

    $this->withHeaders(serviceHdr())
        ->postJson('/api/v1/internal/mark-processed', ['message_ids' => $ids])
        ->assertOk();

    foreach ($messages as $m) {
        expect($m->fresh()->memory_processed)->toBe(true);
    }
});

it('requires message_ids to mark processed', function () {
    $this->withHeaders(serviceHdr())
        ->postJson('/api/v1/internal/mark-processed', [])
        ->assertStatus(422);
});

// ── POST conversations/{id}/typing ────────────────────────────────────────────

it('rejects typing indicator without service token', function () {
    $conversation = Conversation::factory()->create();
    $this->postJson("/api/v1/internal/conversations/{$conversation->id}/typing")
        ->assertUnauthorized();
});

it('typing indicator returns ok with valid token', function () {
    $conversation = Conversation::factory()->create();

    $this->withHeaders(serviceHdr())
        ->postJson("/api/v1/internal/conversations/{$conversation->id}/typing")
        ->assertOk()
        ->assertJsonPath('ok', true);
});

// ── POST conversations/{id}/messages (internal) ───────────────────────────────

it('rejects save message without service token', function () {
    $conversation = Conversation::factory()->create();
    $this->postJson("/api/v1/internal/conversations/{$conversation->id}/messages", [
        'role' => 'assistant', 'content' => 'Hello',
    ])->assertUnauthorized();
});

it('saves assistant message and returns hash id', function () {
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->create(['conversation_id' => $conversation->id]);

    $response = $this->withHeaders(serviceHdr())
        ->postJson("/api/v1/internal/conversations/{$conversation->id}/messages", [
            'role'    => 'assistant',
            'content' => 'Sure, here is the answer.',
        ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['id']]);

    $messageId = $response->json('data.id');
    expect($messageId)->not->toBeNull();

    $message = AssistantMessage::first();
    expect($message->role)->toBe('assistant');
    expect($message->content)->toBe('Sure, here is the answer.');
});
