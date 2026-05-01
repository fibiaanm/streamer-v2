<?php

use App\Domain\Assistant\Jobs\FireEventReminder;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

beforeEach(fn () => Redis::partialMock());

function reminderCtx(): array
{
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);
    $event        = AssistantEvent::factory()->create(['user_id' => $user->id]);

    return [$user, $conversation, $session, $event];
}

it('creates a system message in the user conversation when fired', function () {
    [, $conversation, , $event] = reminderCtx();

    $reminder = EventReminder::factory()->create(['event_id' => $event->id, 'message' => 'Hey!']);

    (new FireEventReminder($reminder->id))->handle();

    $message = AssistantMessage::where('conversation_id', $conversation->id)
        ->where('role', 'system')
        ->first();
    expect($message)->not->toBeNull();
    expect($message->content)->toContain('Hey!');
});

it('marks reminder as fired after execution', function () {
    [, , , $event] = reminderCtx();

    $reminder = EventReminder::factory()->create(['event_id' => $event->id]);

    (new FireEventReminder($reminder->id))->handle();

    expect($reminder->fresh()->status)->toBe('fired');
    expect($reminder->fresh()->fired_at)->not->toBeNull();
});

it('is idempotent: second execution does not create a duplicate message', function () {
    [, $conversation, , $event] = reminderCtx();

    $reminder = EventReminder::factory()->fired()->create(['event_id' => $event->id]);

    (new FireEventReminder($reminder->id))->handle();

    expect(AssistantMessage::where('conversation_id', $conversation->id)->where('role', 'system')->count())->toBe(0);
});

it('stores event_id and reminder_id in metadata_json', function () {
    [, $conversation, , $event] = reminderCtx();

    $reminder = EventReminder::factory()->create(['event_id' => $event->id]);

    (new FireEventReminder($reminder->id))->handle();

    $message = AssistantMessage::where('conversation_id', $conversation->id)->where('role', 'system')->first();
    expect($message->metadata_json['event_id'])->toBe($event->id);
    expect($message->metadata_json['reminder_id'])->toBe($reminder->id);
});
