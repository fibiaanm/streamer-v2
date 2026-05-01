<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RRule\RRule;
use Throwable;

class MaterializeSeriesWindow implements ShouldQueue
{
    use Queueable;

    private const WINDOW_DAYS = 14;

    public function handle(): void
    {
        $now       = now();
        $windowEnd = $now->copy()->addDays(self::WINDOW_DAYS);

        AssistantEvent::whereNull('series_id')
            ->whereNotNull('recurrence_rule')
            ->where('status', 'active')
            ->each(function (AssistantEvent $master) use ($now, $windowEnd) {
                $this->materializeMaster($master, $now, $windowEnd);
            });
    }

    private function materializeMaster(AssistantEvent $master, Carbon $now, Carbon $windowEnd): void
    {
        try {
            $rrule       = new RRule($master->recurrence_rule, $master->event_at);
            $occurrences = $rrule->getOccurrencesBetween($now->toDateTime(), $windowEnd->toDateTime());
        } catch (Throwable $e) {
            Log::warning('assistant.materialize_rrule_error', [
                'master_id' => $master->id,
                'exception' => $e->getMessage(),
            ]);
            return;
        }

        $existing = AssistantEvent::where('series_id', $master->id)
            ->whereIn('occurrence_at', collect($occurrences)->map(fn ($dt) => Carbon::instance($dt)->toDateTimeString()))
            ->get()
            ->keyBy(fn ($e) => Carbon::parse($e->occurrence_at)->toDateTimeString());

        foreach ($occurrences as $dt) {
            $occurrenceAt = Carbon::instance($dt);
            $key          = $occurrenceAt->toDateTimeString();

            if (isset($existing[$key])) {
                continue; // already materialized (active or exception)
            }

            $occurrence = AssistantEvent::create([
                'user_id'       => $master->user_id,
                'series_id'     => $master->id,
                'occurrence_at' => $occurrenceAt,
                'event_at'      => $occurrenceAt,
                'content'       => $master->content,
                'type'          => $master->type,
                'status'        => 'active',
            ]);

            $this->createReminders($occurrence, $master, $occurrenceAt);
        }
    }

    private function createReminders(AssistantEvent $event, AssistantEvent $master, Carbon $eventAt): void
    {
        $template = $master->reminders_template_json ?? [];

        foreach ($template as $tpl) {
            try {
                $fireAt = $eventAt->copy()->modify($tpl['offset']);
            } catch (Throwable) {
                continue;
            }

            if ($fireAt->isPast()) {
                continue;
            }

            $reminder = EventReminder::create([
                'event_id' => $event->id,
                'fire_at'  => $fireAt,
                'message'  => $tpl['message'],
                'status'   => 'pending',
            ]);

            FireEventReminder::dispatch($reminder->id)->delay($fireAt);
        }
    }
}
