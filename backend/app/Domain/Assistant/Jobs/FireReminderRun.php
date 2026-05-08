<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\EventReminder;
use App\Domain\Assistant\Models\ReminderRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class FireReminderRun implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $runId) {}

    public function handle(): void
    {
        $run = ReminderRun::with('user')->find($this->runId);

        if (! $run || $run->status !== 'pending') {
            return;
        }

        $reminders = EventReminder::where('reminder_run_id', $run->id)
            ->with('event')
            ->get();

        if ($reminders->isEmpty()) {
            $run->delete();
            return;
        }

        $conversation = Conversation::firstOrCreate(['user_id' => $run->user_id]);

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->first();

        if (! $session) {
            Log::warning('assistant.fire_reminder_run_no_session', ['run_id' => $this->runId]);
            EventReminder::where('reminder_run_id', $run->id)->update([
                'reminder_run_id' => null,
                'status'          => 'fired',
                'fired_at'        => now(),
            ]);
            $run->update(['status' => 'fired']);
            return;
        }

        $timezone = $run->user->timezone ?? 'UTC';
        App::setLocale($run->user->lang ?? 'en');
        $content  = $this->buildMessage($run, $reminders, $timezone);

        $message = AssistantMessage::create([
            'conversation_id'  => $conversation->id,
            'session_id'       => $session->id,
            'role'             => 'system',
            'channel'          => 'web',
            'content'          => $content,
            'memory_processed' => false,
            'metadata_json'    => ['run_id' => $run->id, 'kind' => $run->kind],
            'created_at'       => now(),
        ]);

        EventReminder::where('reminder_run_id', $run->id)->update([
            'reminder_run_id' => null,
            'status'          => 'fired',
            'fired_at'        => now(),
        ]);

        $run->update(['status' => 'fired']);

        if ($run->kind === 'digest') {
            $reminders
                ->filter(fn ($r) => $r->event?->series_id !== null)
                ->each(fn ($r) => MaterializeNextOccurrence::dispatch($r->event_id)->onQueue('assistant-series'));
        }

        try {
            Redis::connection('pubsub')->publish("assistant.{$session->getHashId()}", json_encode([
                'event' => 'MessageReceived',
                'data'  => [
                    'sessionId' => $session->getHashId(),
                    'message'   => [
                        'id'         => $message->getHashId(),
                        'role'       => $message->role,
                        'content'    => $message->content,
                        'channel'    => $message->channel,
                        'actions'    => $message->actions_json ?? [],
                        'metadata'   => $message->metadata_json ?? [],
                        'created_at' => $message->created_at?->toISOString(),
                    ],
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('assistant.fire_reminder_run_broadcast_failed', ['exception' => $e]);
        }
    }

    private function buildMessage(ReminderRun $run, Collection $reminders, string $timezone): string
    {
        return match ($run->kind) {
            'digest' => $this->buildDigestMessage($reminders, $timezone),
            'ahead'  => $this->buildAheadMessage($reminders),
            default  => $this->buildInlineMessage($reminders),
        };
    }

    private function buildDigestMessage(Collection $reminders, string $timezone): string
    {
        $lines = $reminders->map(function ($r) use ($timezone) {
            $time = $r->event->event_at->copy()->setTimezone($timezone)->format('H:i');

            return __('reminders.digest_item', ['content' => $r->event->content, 'time' => $time]);
        })->join("\n");

        return __('reminders.digest_header') . "\n" . $lines;
    }

    private function buildAheadMessage(Collection $reminders): string
    {
        $lines = $reminders->map(function ($r) {
            $days = (int) now()->startOfDay()->diffInDays($r->event->event_at->startOfDay());

            $when = match (true) {
                $days === 1 => __('reminders.ahead_tomorrow'),
                $days <= 6  => __('reminders.ahead_days',   ['count' => $days]),
                $days <= 13 => __('reminders.ahead_week'),
                $days <= 27 => __('reminders.ahead_weeks',  ['count' => (int) ceil($days / 7)]),
                $days <= 45 => __('reminders.ahead_month'),
                default     => __('reminders.ahead_months', ['count' => (int) round($days / 30)]),
            };

            return __('reminders.ahead_item', ['content' => $r->event->content, 'when' => $when]);
        })->join("\n");

        return __('reminders.ahead_header') . "\n" . $lines;
    }

    private function buildInlineMessage(Collection $reminders): string
    {
        $event       = $reminders->first()->event;
        $minutesLeft = (int) now()->diffInMinutes($event->event_at, false);

        // Queue latency or sweep delay can fire the job a few minutes after event_at.
        // Treat up to 5 min past as "starting now"; beyond that as missed.
        if ($minutesLeft < -5) {
            return __('reminders.inline_missed', ['content' => $event->content]);
        }

        if ($minutesLeft <= 0) {
            return __('reminders.inline_now', ['content' => $event->content]);
        }

        if ($minutesLeft <= 15) {
            return __('reminders.inline_minutes', ['content' => $event->content, 'count' => $minutesLeft]);
        }

        return __('reminders.inline_hour', ['content' => $event->content]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('assistant.fire_reminder_run_failed', [
            'run_id'    => $this->runId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
