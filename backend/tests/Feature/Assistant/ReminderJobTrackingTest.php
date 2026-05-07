<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => config(['queue.default' => 'database']));

// ── Helpers ───────────────────────────────────────────────────────────────────

function fakeJobRow(): int
{
    return DB::table('jobs')->insertGetId([
        'queue'        => 'default',
        'payload'      => json_encode(['displayName' => 'FireEventReminder']),
        'attempts'     => 0,
        'reserved_at'  => null,
        'available_at' => now()->addWeek()->timestamp,
        'created_at'   => now()->timestamp,
    ]);
}

function pendingReminder(int $eventId, int $jobId): EventReminder
{
    return EventReminder::factory()->create([
        'event_id' => $eventId,
        'fire_at'  => now()->addWeek(),
        'status'   => 'pending',
        'job_id'   => $jobId,
    ]);
}

// ── Create: job_id se guarda ──────────────────────────────────────────────────

it('stores job_id on reminder after creating a single event', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', [
            'content'   => 'Call mom',
            'event_at'  => now()->addMonth()->toIso8601String(),
            'type'      => 'reminder',
            'reminders' => [
                ['offset' => '-1 day', 'message' => 'Tomorrow'],
            ],
        ])
        ->assertCreated();

    $reminder = EventReminder::where('status', 'pending')->first();
    expect($reminder)->not->toBeNull();
    expect($reminder->job_id)->not->toBeNull();
});

it('does not set job_id when reminder fire_at is in the past', function () {
    [$user, $enterprise, $token] = asstCtx();

    // event in 3 days, offset -7 days → fire_at 4 days ago
    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', [
            'content'   => 'Past reminder',
            'event_at'  => now()->addDays(3)->toIso8601String(),
            'type'      => 'reminder',
            'reminders' => [
                ['offset' => '-7 days', 'message' => 'Already past'],
            ],
        ])
        ->assertCreated();

    $reminder = EventReminder::first();
    expect($reminder->job_id)->toBeNull();
});

// ── Update: job antiguo se borra, nuevo se crea ───────────────────────────────

it('deletes the queued job when event_at is updated', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event  = AssistantEvent::factory()->create([
        'user_id'                 => $user->id,
        'event_at'                => now()->addMonth(),
        'reminders_template_json' => [['offset' => '-1 day', 'message' => 'Tomorrow']],
    ]);
    $jobId  = fakeJobRow();
    pendingReminder($event->id, $jobId);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'event_at' => now()->addMonths(2)->toIso8601String(),
        ])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
});

it('creates a new job with job_id after recalculating reminders on update', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create([
        'user_id'                 => $user->id,
        'event_at'                => now()->addMonth(),
        'reminders_template_json' => [['offset' => '-1 day', 'message' => 'Tomorrow']],
    ]);
    $jobId = fakeJobRow();
    pendingReminder($event->id, $jobId);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'event_at' => now()->addMonths(2)->toIso8601String(),
        ])
        ->assertOk();

    $newReminder = EventReminder::where('event_id', $event->id)->where('status', 'pending')->first();
    expect($newReminder)->not->toBeNull();
    expect($newReminder->job_id)->not->toBeNull();
    expect($newReminder->job_id)->not->toBe($jobId);
});

it('does not touch jobs when update does not change event_at', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);
    $jobId = fakeJobRow();
    pendingReminder($event->id, $jobId);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'content' => 'Updated content',
        ])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeTrue();
});

// ── Cancel: job se borra ──────────────────────────────────────────────────────

it('deletes the queued job when a single event is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);
    $jobId = fakeJobRow();
    pendingReminder($event->id, $jobId);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$event->getHashId()}/cancel", ['series' => false])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
});

it('deletes queued jobs for future occurrences when series is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master     = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);
    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'event_at' => now()->addDays(3),
    ]);

    $jobId = fakeJobRow();
    pendingReminder($occurrence->id, $jobId);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/cancel", ['series' => true])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
});

it('deletes queued jobs on master when series is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);
    $jobId  = fakeJobRow();
    pendingReminder($master->id, $jobId);

    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'event_at' => now()->addDays(3),
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/cancel", ['series' => true])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
});
