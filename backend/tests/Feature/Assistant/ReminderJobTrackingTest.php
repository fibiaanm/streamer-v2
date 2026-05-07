<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\ReminderRun;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => config(['queue.default' => 'database']));

function fakeJobRow(): int
{
    return DB::table('jobs')->insertGetId([
        'queue'        => 'assistant',
        'payload'      => json_encode(['displayName' => 'FireReminderRun']),
        'attempts'     => 0,
        'reserved_at'  => null,
        'available_at' => now()->addWeek()->timestamp,
        'created_at'   => now()->timestamp,
    ]);
}

function pendingRun(int $userId, int $jobId, string $kind = 'ahead'): ReminderRun
{
    return ReminderRun::create([
        'user_id' => $userId,
        'run_at'  => now()->addWeek(),
        'kind'    => $kind,
        'job_id'  => (string) $jobId,
        'status'  => 'pending',
    ]);
}

function pendingReminderForRun(int $eventId, ReminderRun $run): EventReminder
{
    return EventReminder::create([
        'event_id'        => $eventId,
        'kind'            => $run->kind,
        'fire_at'         => $run->run_at,
        'reminder_run_id' => $run->id,
        'status'          => 'pending',
    ]);
}

// ── Create: ReminderRun gets a job_id ────────────────────────────────────────

it('creates a ReminderRun with job_id when creating a future event', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/events', [
            'content'  => 'Call mom',
            'event_at' => now()->addMonth()->toIso8601String(),
            'type'     => 'reminder',
        ])
        ->assertCreated();

    $run = ReminderRun::where('user_id', $user->id)->where('status', 'pending')->first();
    expect($run)->not->toBeNull();
    expect($run->job_id)->not->toBeNull();
});

// ── Update: old run cancelled, new one created ────────────────────────────────

it('deletes the queued job when event_at is updated and run becomes empty', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => now()->addMonth()]);
    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($event->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'event_at' => now()->addMonths(2)->toIso8601String(),
        ])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
    expect(ReminderRun::find($run->id))->toBeNull();
});

it('creates a new ReminderRun with a new job after recalculating on update', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id, 'event_at' => now()->addMonth()]);
    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($event->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'event_at' => now()->addMonths(2)->toIso8601String(),
        ])
        ->assertOk();

    $newRuns = ReminderRun::where('user_id', $user->id)->where('status', 'pending')->get();
    expect($newRuns->count())->toBeGreaterThan(0);
    expect($newRuns->pluck('job_id')->filter()->isNotEmpty())->toBeTrue();
});

it('does not touch runs when update does not change event_at', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);
    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($event->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/events/{$event->getHashId()}", [
            'content' => 'Updated content',
        ])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeTrue();
});

// ── Cancel: job se borra ──────────────────────────────────────────────────────

it('deletes the queued job and run when a single event is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);
    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($event->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$event->getHashId()}/cancel", ['series' => false])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
    expect(ReminderRun::find($run->id))->toBeNull();
});

it('deletes queued jobs for future occurrences when series is cancelled', function () {
    [$user, $enterprise, $token] = asstCtx();

    $master     = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);
    $occurrence = AssistantEvent::factory()->occurrence($master)->create(['event_at' => now()->addDays(3)]);

    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($occurrence->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$occurrence->getHashId()}/cancel", ['series' => true])
        ->assertOk();

    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeFalse();
});

it('preserves a shared run when only one event is cancelled and others share the run', function () {
    [$user, $enterprise, $token] = asstCtx();

    $event1 = AssistantEvent::factory()->create(['user_id' => $user->id]);
    $event2 = AssistantEvent::factory()->create(['user_id' => $user->id]);

    $jobId = fakeJobRow();
    $run   = pendingRun($user->id, $jobId);
    pendingReminderForRun($event1->id, $run);
    pendingReminderForRun($event2->id, $run);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/events/{$event1->getHashId()}/cancel", ['series' => false])
        ->assertOk();

    // Run still has event2's reminder → job must stay
    expect(DB::table('jobs')->where('id', $jobId)->exists())->toBeTrue();
    expect(ReminderRun::find($run->id))->not->toBeNull();
});
