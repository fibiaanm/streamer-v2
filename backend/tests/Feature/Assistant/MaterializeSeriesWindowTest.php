<?php

use App\Domain\Assistant\Jobs\MaterializeSeriesWindow;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('creates occurrence rows + reminders for master within next 14 days', function () {
    $user = User::factory()->create();

    AssistantEvent::factory()->master('FREQ=DAILY')->create([
        'user_id'                 => $user->id,
        'event_at'                => now()->startOfDay(),
        'reminders_template_json' => [['offset' => '-1 day', 'message' => 'Tomorrow']],
    ]);

    (new MaterializeSeriesWindow)->handle();

    expect(AssistantEvent::whereNotNull('series_id')->count())->toBeGreaterThan(0);
    expect(EventReminder::count())->toBeGreaterThan(0);
});

it('does not duplicate rows when occurrence already materialized', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY')->create([
        'user_id'  => $user->id,
        'event_at' => now()->startOfDay(),
    ]);

    AssistantEvent::factory()->occurrence($master)->create([
        'event_at'      => now()->startOfDay(),
        'occurrence_at' => now()->startOfDay(),
    ]);

    $countBefore = AssistantEvent::whereNotNull('series_id')->count();

    (new MaterializeSeriesWindow)->handle();

    // the already-materialized slot should not be duplicated
    $countAfter = AssistantEvent::whereNotNull('series_id')->count();
    expect($countAfter)->toBeGreaterThanOrEqual($countBefore);
    expect(AssistantEvent::where('series_id', $master->id)
        ->where('occurrence_at', now()->startOfDay()->toDateTimeString())
        ->count())->toBe(1);
});

it('ignores masters with status=cancelled', function () {
    $user = User::factory()->create();

    AssistantEvent::factory()->master('FREQ=DAILY')->cancelled()->create([
        'user_id'  => $user->id,
        'event_at' => now()->startOfDay(),
    ]);

    (new MaterializeSeriesWindow)->handle();

    expect(AssistantEvent::whereNotNull('series_id')->count())->toBe(0);
});

it('ignores slots with existing cancelled exception', function () {
    $user   = User::factory()->create();
    $master = AssistantEvent::factory()->master('FREQ=DAILY')->create([
        'user_id'  => $user->id,
        'event_at' => now()->startOfDay(),
    ]);

    // cancelled exception for today's slot
    AssistantEvent::factory()->occurrence($master)->cancelled()->create([
        'event_at'      => now()->startOfDay(),
        'occurrence_at' => now()->startOfDay(),
    ]);

    (new MaterializeSeriesWindow)->handle();

    // the cancelled slot should not have an active duplicate
    expect(
        AssistantEvent::where('series_id', $master->id)
            ->where('status', 'active')
            ->whereDate('occurrence_at', now()->toDateString())
            ->count()
    )->toBe(0);
});
