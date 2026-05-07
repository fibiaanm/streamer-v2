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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class FireReminderRun implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $runId) {}

    public function handle(): void
    {
        $run = ReminderRun::find($this->runId);

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
            return;
        }

        $content = $this->buildMessage($run, $reminders);

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

    private function buildMessage(ReminderRun $run, Collection $reminders): string
    {
        return match ($run->kind) {
            'digest' => $this->buildDigestMessage($reminders),
            'ahead'  => $this->buildAheadMessage($reminders),
            default  => $this->buildInlineMessage($reminders),
        };
    }

    private function buildDigestMessage(Collection $reminders): string
    {
        $lines = $reminders->map(fn ($r) => '- ' . $r->event->content . ' a las ' . $r->event->event_at->format('H:i'))->join("\n");

        return "Estos son tus eventos de hoy:\n{$lines}";
    }

    private function buildAheadMessage(Collection $reminders): string
    {
        $lines = $reminders->map(function ($r) {
            $days = (int) now()->startOfDay()->diffInDays($r->event->event_at->startOfDay());

            $when = match (true) {
                $days === 1 => 'mañana',
                $days <= 6  => "en {$days} días",
                $days <= 13 => 'en 1 semana',
                $days <= 27 => 'en ' . ceil($days / 7) . ' semanas',
                $days <= 45 => 'en 1 mes',
                default     => 'en ' . round($days / 30) . ' meses',
            };

            return "- {$r->event->content}: {$when}";
        })->join("\n");

        return "Recordatorio de próximos eventos:\n{$lines}";
    }

    private function buildInlineMessage(Collection $reminders): string
    {
        $event       = $reminders->first()->event;
        $minutesLeft = (int) now()->diffInMinutes($event->event_at, false);

        if ($minutesLeft <= 15) {
            return "Tu evento «{$event->content}» empieza en {$minutesLeft} minutos.";
        }

        return "Tu evento «{$event->content}» empieza en 1 hora.";
    }

    public function failed(Throwable $exception): void
    {
        Log::error('assistant.fire_reminder_run_failed', [
            'run_id'    => $this->runId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
