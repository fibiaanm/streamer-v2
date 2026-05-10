<?php

use App\Domain\Assistant\Jobs\MaterializeNextOccurrence;
use App\Domain\Assistant\Jobs\SweepSeriesChains;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('dispatches MaterializeNextOccurrence for an occurrence with no successor', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=WEEKLY')->create(['user_id' => $user->id]);

    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDay(),
        'occurrence_at' => now()->subDay(),
    ]);

    (new SweepSeriesChains)->handle();

    Queue::assertPushed(MaterializeNextOccurrence::class);
});

it('does not dispatch when a successor already exists', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=WEEKLY')->create(['user_id' => $user->id]);

    $first = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDay(),
        'occurrence_at' => now()->subDay(),
    ]);

    // successor already exists
    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->addWeek(),
        'occurrence_at' => now()->addWeek(),
    ]);

    (new SweepSeriesChains)->handle();

    Queue::assertNothingPushed();
});

it('does not dispatch for cancelled occurrences', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);

    AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDay(),
        'occurrence_at' => now()->subDay(),
    ]);

    (new SweepSeriesChains)->handle();

    Queue::assertNothingPushed();
});

it('does not dispatch for occurrences in the future', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY')->create(['user_id' => $user->id]);

    AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->addDay(),
        'occurrence_at' => now()->addDay(),
    ]);

    (new SweepSeriesChains)->handle();

    Queue::assertNothingPushed();
});

it('dispatches when the only successor is cancelled', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY;INTERVAL=2')->create(['user_id' => $user->id]);

    $past = AssistantEvent::factory()->occurrence($master)->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDays(4),
        'occurrence_at' => now()->subDays(4),
    ]);

    // Only successor is cancelled — chain is broken but should be rescued
    AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'user_id'       => $master->user_id,
        'event_at'      => now()->subDays(2),
        'occurrence_at' => now()->subDays(2),
    ]);

    (new SweepSeriesChains)->handle();

    Queue::assertPushed(MaterializeNextOccurrence::class, fn ($job) => $job->occurrenceId === $past->id);
});

it('dispatches for each occurrence that needs a successor across multiple series', function () {
    $user = User::factory()->create();

    foreach (['FREQ=DAILY', 'FREQ=WEEKLY'] as $rrule) {
        $master = AssistantEvent::factory()->master($rrule)->create(['user_id' => $user->id]);
        AssistantEvent::factory()->occurrence($master)->create([
            'user_id'       => $master->user_id,
            'event_at'      => now()->subHour(),
            'occurrence_at' => now()->subHour(),
        ]);
    }

    (new SweepSeriesChains)->handle();

    Queue::assertPushed(MaterializeNextOccurrence::class, 2);
});
