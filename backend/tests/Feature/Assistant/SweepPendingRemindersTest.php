<?php

use App\Domain\Assistant\Jobs\FireEventReminder;
use App\Domain\Assistant\Jobs\SweepPendingReminders;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('dispatches FireEventReminder only for reminders with fire_at <= now', function () {
    $user  = User::factory()->create();
    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);

    EventReminder::factory()->past()->create(['event_id' => $event->id]);
    EventReminder::factory()->create(['event_id' => $event->id, 'fire_at' => now()->addHour()]);

    (new SweepPendingReminders)->handle();

    Queue::assertPushed(FireEventReminder::class, 1);
});

it('does not dispatch for already fired reminders', function () {
    $user  = User::factory()->create();
    $event = AssistantEvent::factory()->create(['user_id' => $user->id]);

    EventReminder::factory()->fired()->past()->create(['event_id' => $event->id]);

    (new SweepPendingReminders)->handle();

    Queue::assertNothingPushed();
});
