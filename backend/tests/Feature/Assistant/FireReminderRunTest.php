<?php

use App\Domain\Assistant\Jobs\FireReminderRun;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\ReminderRun;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

beforeEach(fn () => Redis::partialMock());

function runCtx(string $kind = 'ahead'): array
{
    $user         = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $session      = AssistantSession::factory()->create(['conversation_id' => $conversation->id]);
    $event        = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => now()->addDay()]);

    $run = ReminderRun::create([
        'user_id' => $user->id,
        'run_at'  => now()->addHour(),
        'kind'    => $kind,
        'status'  => 'pending',
    ]);

    $reminder = EventReminder::create([
        'event_id'        => $event->id,
        'kind'            => $kind,
        'fire_at'         => now()->addHour(),
        'reminder_run_id' => $run->id,
        'status'          => 'pending',
    ]);

    return [$user, $conversation, $session, $event, $run, $reminder];
}

it('creates a system message in the user conversation when fired', function () {
    [, $conversation, , , $run] = runCtx();

    (new FireReminderRun($run->id))->handle();

    $message = AssistantMessage::where('conversation_id', $conversation->id)
        ->where('role', 'system')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->not->toBeEmpty();
});

it('sets reminder_run_id to null and status to fired after execution', function () {
    [, , , , $run, $reminder] = runCtx();

    (new FireReminderRun($run->id))->handle();

    $fresh = $reminder->fresh();
    expect($fresh->reminder_run_id)->toBeNull();
    expect($fresh->status)->toBe('fired');
    expect($fresh->fired_at)->not->toBeNull();
});

it('marks the run as fired after execution', function () {
    [, , , , $run] = runCtx();

    (new FireReminderRun($run->id))->handle();

    expect($run->fresh()->status)->toBe('fired');
});

it('is idempotent: does not create a duplicate message if run already fired', function () {
    [, $conversation, , , $run] = runCtx();

    $run->update(['status' => 'fired']);

    (new FireReminderRun($run->id))->handle();

    expect(AssistantMessage::where('conversation_id', $conversation->id)->where('role', 'system')->count())->toBe(0);
});

it('stores run_id and kind in metadata_json', function () {
    [, $conversation, , , $run] = runCtx();

    (new FireReminderRun($run->id))->handle();

    $message = AssistantMessage::where('conversation_id', $conversation->id)->where('role', 'system')->first();
    expect($message->metadata_json['run_id'])->toBe($run->id);
    expect($message->metadata_json['kind'])->toBe('ahead');
});

it('builds a digest message listing events when kind is digest', function () {
    [, $conversation, , $event, $run] = runCtx('digest');

    (new FireReminderRun($run->id))->handle();

    $message = AssistantMessage::where('conversation_id', $conversation->id)->where('role', 'system')->first();
    expect($message->content)->toContain($event->content);
    expect($message->content)->toContain('hoy');
});

it('digest formats event time in user timezone, not UTC', function () {
    $user = User::factory()->create(['timezone' => 'America/New_York']); // UTC-4 en verano
    Conversation::factory()->create(['user_id' => $user->id]);
    AssistantSession::factory()->create([
        'conversation_id' => Conversation::where('user_id', $user->id)->value('id'),
    ]);

    // Evento a las 15:00 UTC = 11:00 America/New_York
    $event = AssistantEvent::factory()->create([
        'user_id'  => $user->id,
        'event_at' => '2026-06-01 15:00:00',
    ]);

    $run = ReminderRun::create(['user_id' => $user->id, 'run_at' => now(), 'kind' => 'digest', 'status' => 'pending']);
    EventReminder::create(['event_id' => $event->id, 'kind' => 'digest', 'fire_at' => now(), 'reminder_run_id' => $run->id, 'status' => 'pending']);

    (new FireReminderRun($run->id))->handle();

    $message = AssistantMessage::where('role', 'system')->first();
    expect($message->content)->toContain('11:00');
    expect($message->content)->not->toContain('15:00');
});

it('marks run and reminders as fired and does not loop when no session exists', function () {
    $user = User::factory()->create();
    Conversation::factory()->create(['user_id' => $user->id]);
    // No session created intentionally

    $event    = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => now()->addDay()]);
    $run      = ReminderRun::create(['user_id' => $user->id, 'run_at' => now(), 'kind' => 'ahead', 'status' => 'pending']);
    $reminder = EventReminder::create(['event_id' => $event->id, 'kind' => 'ahead', 'fire_at' => now(), 'reminder_run_id' => $run->id, 'status' => 'pending']);

    (new FireReminderRun($run->id))->handle();

    expect($run->fresh()->status)->toBe('fired');
    expect($reminder->fresh()->status)->toBe('fired');
    expect($reminder->fresh()->reminder_run_id)->toBeNull();
    expect(AssistantMessage::count())->toBe(0);
});

it('deletes the run if it has no reminders when fired', function () {
    $user    = User::factory()->create();
    $run     = ReminderRun::create(['user_id' => $user->id, 'run_at' => now(), 'kind' => 'ahead', 'status' => 'pending']);

    (new FireReminderRun($run->id))->handle();

    expect(ReminderRun::find($run->id))->toBeNull();
});
