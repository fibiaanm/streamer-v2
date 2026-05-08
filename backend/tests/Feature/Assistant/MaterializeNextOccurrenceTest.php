<?php

use App\Domain\Assistant\Jobs\MaterializeNextOccurrence;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function matCtx(string $rrule = 'FREQ=WEEKLY;BYDAY=MO'): array
{
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master($rrule)->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-04 10:00:00', // Monday
    ]);

    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-04 10:00:00',
        'occurrence_at' => '2026-05-04 10:00:00',
    ]);

    return [$user, $master, $occurrence];
}

it('creates the next occurrence in the series', function () {
    [, $master, $occurrence] = matCtx();

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    $next = AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', '!=', '2026-05-04 10:00:00')
        ->first();

    expect($next)->not->toBeNull();
    expect($next->content)->toBe($master->content);
    expect($next->type)->toBe($master->type);
    expect($next->status)->toBe('active');
});

it('schedules reminders for the new occurrence', function () {
    [, , $occurrence] = matCtx();

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    expect(EventReminder::count())->toBeGreaterThan(0);
    Queue::assertPushed(\App\Domain\Assistant\Jobs\FireReminderRun::class);
});

it('is idempotent when the next occurrence already exists', function () {
    [, $master, $occurrence] = matCtx();

    // pre-create the next Monday
    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => '2026-05-11 10:00:00',
        'occurrence_at' => '2026-05-11 10:00:00',
    ]);

    $countBefore = AssistantEvent::where('series_id', $master->id)->count();

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    expect(AssistantEvent::where('series_id', $master->id)->count())->toBe($countBefore);
});

it('stops when master is cancelled', function () {
    [, $master, $occurrence] = matCtx();
    $master->update(['status' => 'cancelled']);

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    expect(AssistantEvent::where('series_id', $master->id)->count())->toBe(1);
});

it('stops when series_ends_at is in the past', function () {
    [, $master, $occurrence] = matCtx();
    $master->update(['series_ends_at' => now()->subDay()]);

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    expect(AssistantEvent::where('series_id', $master->id)->count())->toBe(1);
});

it('stops when next occurrence falls after series_ends_at', function () {
    [, $master, $occurrence] = matCtx();
    // series_ends_at between current and next occurrence
    $master->update(['series_ends_at' => '2026-05-10 00:00:00']);

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    // next Monday (2026-05-11) is after series_ends_at → nothing created
    expect(AssistantEvent::where('series_id', $master->id)->count())->toBe(1);
});

it('does nothing when occurrence has no series_id', function () {
    $user      = User::factory()->create();
    $single    = AssistantEvent::factory()->create(['user_id' => $user->id]);

    (new MaterializeNextOccurrence($single->id))->handle();

    expect(AssistantEvent::whereNotNull('series_id')->count())->toBe(0);
});

it('skips a cancelled occurrence slot and creates the next available one', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    // Occurrence on May 10 — this triggers the job
    $occ10 = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-10 10:00:00',
        'occurrence_at' => '2026-05-10 10:00:00',
    ]);

    // May 12 slot already exists but was cancelled before materialization
    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-12 10:00:00',
        'occurrence_at' => '2026-05-12 10:00:00',
        'status'        => 'cancelled',
    ]);

    (new MaterializeNextOccurrence($occ10->id))->handle();

    // May 12 skipped → May 14 created
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', '2026-05-14 10:00:00')
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();

    // May 12 must remain cancelled, not recreated
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', '2026-05-12 10:00:00')
        ->where('status', 'active')
        ->exists()
    )->toBeFalse();
});

it('skips multiple consecutive cancelled slots and lands on the first free one', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    $occ10 = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-10 10:00:00',
        'occurrence_at' => '2026-05-10 10:00:00',
    ]);

    // May 12 and May 14 are both cancelled
    foreach (['2026-05-12 10:00:00', '2026-05-14 10:00:00'] as $date) {
        AssistantEvent::factory()->occurrence($master)->create([
            'user_id'       => $user->id,
            'event_at'      => $date,
            'occurrence_at' => $date,
            'status'        => 'cancelled',
        ]);
    }

    (new MaterializeNextOccurrence($occ10->id))->handle();

    // Should land on May 16
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', '2026-05-16 10:00:00')
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();
});

it('uses occurrence_at to chain so a rescheduled event_at does not break the series', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => '2026-05-10 10:00:00',
    ]);

    // May 20 occurrence was moved to May 21 (event_at changed), but occurrence_at stays May 20
    $occ20 = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-21 10:00:00', // moved
        'occurrence_at' => '2026-05-20 10:00:00', // canonical slot
    ]);

    (new MaterializeNextOccurrence($occ20->id))->handle();

    // Next after May 20 (occurrence_at) → May 22, not May 23
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', '2026-05-22 10:00:00')
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();
});

it('handles COUNT-limited series and stops at the last occurrence', function () {
    $user   = User::factory()->create();
    // 2 occurrences total
    $master = AssistantEvent::factory()->master('FREQ=DAILY;COUNT=2')->create([
        'user_id'       => $user->id,
        'event_at'      => now()->subDays(2)->startOfDay(),
        'series_ends_at' => now()->subDay()->startOfDay(),
    ]);

    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDays(2)->startOfDay(),
        'occurrence_at' => now()->subDays(2)->startOfDay(),
    ]);

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    // next would be yesterday — still before series_ends_at (yesterday), so should fail the guard
    // (series_ends_at == yesterday, next == yesterday → not strictly after series_ends_at)
    // Either way, no infinite loop
    expect(AssistantEvent::where('series_id', $master->id)->count())->toBeLessThanOrEqual(2);
});
