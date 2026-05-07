<?php

use App\Domain\Assistant\Jobs\FireReminderRun;
use App\Domain\Assistant\Jobs\SweepPendingReminders;
use App\Domain\Assistant\Models\ReminderRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('dispatches FireReminderRun only for runs with run_at <= now', function () {
    $user = User::factory()->create();

    ReminderRun::create(['user_id' => $user->id, 'run_at' => now()->subMinute(), 'kind' => 'ahead', 'status' => 'pending']);
    ReminderRun::create(['user_id' => $user->id, 'run_at' => now()->addHour(),   'kind' => 'ahead', 'status' => 'pending']);

    (new SweepPendingReminders)->handle();

    Queue::assertPushed(FireReminderRun::class, 1);
});

it('does not dispatch for already fired runs', function () {
    $user = User::factory()->create();

    ReminderRun::create(['user_id' => $user->id, 'run_at' => now()->subMinute(), 'kind' => 'ahead', 'status' => 'fired']);

    (new SweepPendingReminders)->handle();

    Queue::assertNothingPushed();
});
