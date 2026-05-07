<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Jobs\FireReminderRun;
use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\ReminderRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class ReminderScheduler
{
    public static function scheduleForEvent(AssistantEvent $event, string $timezone): void
    {
        $entries = self::fromMatrix($event->event_at, $timezone);

        foreach ($entries as ['kind' => $kind, 'fire_at' => $fireAt]) {
            self::schedule($event, $kind, $fireAt, $event->user_id, $timezone);
        }
    }

    public static function releaseByEventIds(array $eventIds): void
    {
        if (empty($eventIds)) {
            return;
        }

        $runIds = EventReminder::whereIn('event_id', $eventIds)
            ->whereNotNull('reminder_run_id')
            ->pluck('reminder_run_id')
            ->unique()
            ->values()
            ->all();

        EventReminder::whereIn('event_id', $eventIds)
            ->whereNotNull('reminder_run_id')
            ->update(['reminder_run_id' => null, 'status' => 'cancelled']);

        foreach ($runIds as $runId) {
            $run = ReminderRun::find($runId);
            $run?->cancelIfEmpty();
        }
    }

    private static function schedule(
        AssistantEvent $event,
        string $kind,
        Carbon $fireAt,
        int $userId,
        string $timezone,
    ): void {
        if ($fireAt->isPast()) {
            return;
        }

        DB::transaction(function () use ($event, $kind, $fireAt, $userId) {
            $run = ReminderRun::firstOrCreate(
                ['user_id' => $userId, 'run_at' => $fireAt->toDateTimeString(), 'kind' => $kind, 'status' => 'pending'],
            );

            if (! $run->job_id) {
                $jobId = Queue::laterOn('assistant', $fireAt, new FireReminderRun($run->id));
                $run->update(['job_id' => (string) $jobId]);
            }

            EventReminder::create([
                'event_id'        => $event->id,
                'kind'            => $kind,
                'fire_at'         => $fireAt,
                'status'          => 'pending',
                'reminder_run_id' => $run->id,
            ]);
        });
    }

    public static function fromMatrix(Carbon $eventAt, string $timezone): array
    {
        $nowLocal    = now()->setTimezone($timezone);
        $eventLocal  = $eventAt->copy()->setTimezone($timezone);

        if ($eventLocal->isSameDay($nowLocal)) {
            return self::inlineEntries($eventAt);
        }

        $diffInDays = (int) $nowLocal->startOfDay()->diffInDays($eventLocal->copy()->startOfDay());

        $entries = [];

        $digestAt = self::atTimeInTz($eventAt, 6, 0, $timezone);
        if ($digestAt->isFuture()) {
            $entries[] = ['kind' => 'digest', 'fire_at' => $digestAt];
        }

        foreach (self::aheadOffsets($diffInDays) as $offset) {
            $aheadDay = $eventAt->copy()->modify($offset);
            $fireAt   = self::atTimeInTz($aheadDay, 10, 0, $timezone);
            if ($fireAt->isFuture()) {
                $entries[] = ['kind' => 'ahead', 'fire_at' => $fireAt];
            }
        }

        return $entries;
    }

    private static function inlineEntries(Carbon $eventAt): array
    {
        $entries = [];

        $minus1h  = $eventAt->copy()->subHour();
        $minus10m = $eventAt->copy()->subMinutes(10);

        if ($minus1h->isFuture()) {
            $entries[] = ['kind' => 'inline', 'fire_at' => $minus1h];
        }

        if ($minus10m->isFuture()) {
            $entries[] = ['kind' => 'inline', 'fire_at' => $minus10m];
        }

        // Event is imminent (< 10 min): fire exactly at event_at so "remind me in 5m" works
        if (empty($entries) && $eventAt->isFuture()) {
            $entries[] = ['kind' => 'inline', 'fire_at' => $eventAt->copy()];
        }

        return $entries;
    }

    private static function aheadOffsets(int $diffInDays): array
    {
        if ($diffInDays >= 365) {
            return ['-1 month', '-1 week', '-1 day'];
        }

        if ($diffInDays >= 30) {
            return ['-1 week', '-1 day'];
        }

        if ($diffInDays > 2) {
            return ['-1 day'];
        }

        return [];
    }

    private static function atTimeInTz(Carbon $dateUtc, int $hour, int $minute, string $tz): Carbon
    {
        $local = $dateUtc->copy()->setTimezone($tz);

        return Carbon::create(
            $local->year,
            $local->month,
            $local->day,
            $hour,
            $minute,
            0,
            $tz,
        )->utc();
    }
}
