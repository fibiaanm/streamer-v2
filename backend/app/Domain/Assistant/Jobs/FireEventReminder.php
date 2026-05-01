<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class FireEventReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $reminderId) {}

    public function handle(): void
    {
        $reminder = EventReminder::with(['event.referenceable'])->find($this->reminderId);

        if (! $reminder || $reminder->status !== 'pending') {
            return;
        }

        $event = $reminder->event;
        if (! $event) {
            return;
        }

        $conversation = Conversation::firstOrCreate(['user_id' => $event->user_id]);

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->first();

        if (! $session) {
            Log::warning('assistant.fire_reminder_no_session', ['reminder_id' => $this->reminderId]);
            return;
        }

        $content = $reminder->message;
        if ($event->referenceable && method_exists($event->referenceable, 'eventReferenceLabel')) {
            $content .= ' — ' . $event->referenceable->eventReferenceLabel();
        }

        $metadata = [
            'event_id'    => $event->id,
            'reminder_id' => $reminder->id,
        ];

        if ($event->referenceable_type) {
            $metadata['referenceable_type'] = $event->referenceable_type;
            $metadata['referenceable_id']   = $event->referenceable_id;
        }

        $message = AssistantMessage::create([
            'conversation_id'  => $conversation->id,
            'session_id'       => $session->id,
            'role'             => 'system',
            'channel'          => 'web',
            'content'          => $content,
            'memory_processed' => false,
            'metadata_json'    => $metadata,
            'created_at'       => now(),
        ]);

        $reminder->update([
            'status'   => 'fired',
            'fired_at' => now(),
        ]);

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
            Log::error('assistant.fire_reminder_broadcast_failed', ['exception' => $e]);
        }
    }
}
