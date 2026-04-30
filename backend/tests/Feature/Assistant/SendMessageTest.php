<?php

use App\Domain\Assistant\Jobs\ProcessAssistantMessage;
use App\Domain\Assistant\Jobs\ProcessMessageAttachment;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\Conversation;
use App\Exceptions\ErrorCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

it('creates a user message and dispatches ProcessAssistantMessage', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/messages', ['content' => 'Hello assistant'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['message_id', 'session_id', 'status']]);

    $message = AssistantMessage::first();
    expect($message)->not->toBeNull();
    expect($message->role)->toBe('user');
    expect($message->channel)->toBe('web');

    Queue::assertPushedOn('assistant', ProcessAssistantMessage::class);
});

it('defaults channel to web when not specified', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/messages', ['content' => 'Hello'])
        ->assertOk();

    expect(AssistantMessage::first()->channel)->toBe('web');
});

it('returns queued status in response', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/messages', ['content' => 'Ping'])
        ->assertOk();

    expect($response->json('data.status'))->toBe('queued');
    expect($response->json('data.message_id'))->not->toBeNull();
});

it('dispatches ProcessMessageAttachment chained with ProcessAssistantMessage when file attached', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $file = UploadedFile::fake()->create('audio.mp3', 500, 'audio/mpeg');

    $this->withHeaders(asstHdr($token, $enterprise))
        ->post('/api/v1/assistant/messages', [
            'content'    => 'Listen to this',
            'attachment' => $file,
        ], ['Accept' => 'application/json'] + asstHdr($token, $enterprise))
        ->assertOk();

    Queue::assertPushedOn('assistant', ProcessMessageAttachment::class);
    Queue::assertNotPushed(ProcessAssistantMessage::class);
});

it('rejects unsupported mime type with 422', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $file = UploadedFile::fake()->create('doc.txt', 10, 'text/plain');

    $this->withHeaders(asstHdr($token, $enterprise))
        ->post('/api/v1/assistant/messages', [
            'content'    => 'Check this',
            'attachment' => $file,
        ], ['Accept' => 'application/json'] + asstHdr($token, $enterprise))
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);

    Queue::assertNotPushed(ProcessAssistantMessage::class);
    Queue::assertNotPushed(ProcessMessageAttachment::class);
});

it('rejects request with no content and no attachment', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/messages', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('creates a new session when no active session exists and returns session_id', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/messages', ['content' => 'First message'])
        ->assertOk();

    expect($response->json('data.session_id'))->not->toBeNull();

    $session = \App\Domain\Assistant\Models\AssistantSession::first();
    expect($session)->not->toBeNull();
    expect($session->getHashId())->toBe($response->json('data.session_id'));
});

it('reuses active session on subsequent messages', function () {
    Queue::fake();
    [$user, $enterprise, $token] = asstCtx();

    $headers = asstHdr($token, $enterprise);

    $first  = $this->withHeaders($headers)->postJson('/api/v1/assistant/messages', ['content' => 'msg 1'])->json('data.session_id');
    $second = $this->withHeaders($headers)->postJson('/api/v1/assistant/messages', ['content' => 'msg 2'])->json('data.session_id');

    expect($first)->toBe($second);
    expect(\App\Domain\Assistant\Models\AssistantSession::count())->toBe(1);
});

it('returns 401 without authentication', function () {
    $this->postJson('/api/v1/assistant/messages', ['content' => 'hi'])->assertUnauthorized();
});
