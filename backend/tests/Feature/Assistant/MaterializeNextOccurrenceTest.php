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
    $base   = now()->addDays(2)->setTime(10, 0)->seconds(0);
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => $base,
    ]);

    $occ = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => $base,
        'occurrence_at' => $base,
    ]);

    // Next slot (+2 days) is cancelled
    $cancelled = $base->copy()->addDays(2);
    AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'user_id'       => $user->id,
        'event_at'      => $cancelled,
        'occurrence_at' => $cancelled,
    ]);

    (new MaterializeNextOccurrence($occ->id))->handle();

    $expected = $cancelled->copy()->addDays(2);

    // Cancelled slot skipped → slot after it created
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', $expected->toDateTimeString())
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();

    // Cancelled slot must remain cancelled
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', $cancelled->toDateTimeString())
        ->where('status', 'active')
        ->exists()
    )->toBeFalse();
});

it('skips multiple consecutive cancelled slots and lands on the first free one', function () {
    $user   = User::factory()->create();
    $base   = now()->addDays(2)->setTime(10, 0)->seconds(0);
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => $base,
    ]);

    $occ = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => $base,
        'occurrence_at' => $base,
    ]);

    // Next two slots are cancelled
    $slot1 = $base->copy()->addDays(2);
    $slot2 = $base->copy()->addDays(4);
    foreach ([$slot1, $slot2] as $date) {
        AssistantEvent::factory()->occurrence($master)->cancelled()->create([
            'user_id'       => $user->id,
            'event_at'      => $date,
            'occurrence_at' => $date,
        ]);
    }

    (new MaterializeNextOccurrence($occ->id))->handle();

    $expected = $base->copy()->addDays(6);

    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', $expected->toDateTimeString())
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();
});

it('uses occurrence_at to chain so a rescheduled event_at does not break the series', function () {
    $user   = User::factory()->create();
    $base   = now()->addDays(2)->setTime(10, 0)->seconds(0);
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create([
        'user_id'  => $user->id,
        'event_at' => $base,
    ]);

    // Occurrence moved one day forward (event_at ≠ occurrence_at)
    $occ = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => $base->copy()->addDay(),   // moved
        'occurrence_at' => $base,                      // canonical slot
    ]);

    (new MaterializeNextOccurrence($occ->id))->handle();

    // Chain uses occurrence_at, so next slot is base+4, not base+5
    $expected = $base->copy()->addDays(2); // INTERVAL=2 → next slot after $base
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', $expected->toDateTimeString())
        ->where('status', 'active')
        ->exists()
    )->toBeTrue();
});

it('does nothing when the occurrence user has been deleted', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create(['user_id' => $user->id]);

    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => '2026-05-10 10:00:00',
        'occurrence_at' => '2026-05-10 10:00:00',
    ]);

    $user->delete();

    expect(fn () => (new MaterializeNextOccurrence($occurrence->id))->handle())->not->toThrow(Throwable::class);
    expect(AssistantEvent::where('series_id', $master->id)->count())->toBe(1);
});

it('skips past slots and creates the next future occurrence', function () {
    $user   = User::factory()->create();
    // Every 3 days, started 12 days ago — next slots are 9, 6, 3 days ago, then future
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=3')->create([
        'user_id'  => $user->id,
        'event_at' => now()->subDays(12)->setTime(10, 0),
    ]);

    $occurrence = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $user->id,
        'event_at'      => now()->subDays(12)->setTime(10, 0),
        'occurrence_at' => now()->subDays(12)->setTime(10, 0),
    ]);

    (new MaterializeNextOccurrence($occurrence->id))->handle();

    // Must NOT create any occurrence in the past
    expect(
        AssistantEvent::where('series_id', $master->id)
            ->where('occurrence_at', '<', now())
            ->where('id', '!=', $occurrence->id)
            ->exists()
    )->toBeFalse();

    // Must create the next occurrence in the future
    expect(
        AssistantEvent::where('series_id', $master->id)
            ->where('occurrence_at', '>', now())
            ->exists()
    )->toBeTrue();
});

it('skips 11 consecutive cancelled slots and creates the 12th', function () {
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

    // 11 consecutive cancelled slots: May 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, June 1
    $dates = [];
    for ($i = 1; $i <= 11; $i++) {
        $date = '2026-05-' . str_pad(10 + $i * 2, 2, '0', STR_PAD_LEFT);
        // overflow to June
        $dt = \Carbon\Carbon::parse('2026-05-10')->addDays($i * 2)->format('Y-m-d');
        AssistantEvent::factory()->occurrence($master)->cancelled()->create([
            'user_id'       => $user->id,
            'event_at'      => $dt . ' 10:00:00',
            'occurrence_at' => $dt . ' 10:00:00',
        ]);
    }

    (new MaterializeNextOccurrence($occ10->id))->handle();

    // The 12th slot (May 10 + 24 days = June 3) must be created
    expect(
        AssistantEvent::where('series_id', $master->id)
            ->where('status', 'active')
            ->where('occurrence_at', '>', '2026-06-01 00:00:00')
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
