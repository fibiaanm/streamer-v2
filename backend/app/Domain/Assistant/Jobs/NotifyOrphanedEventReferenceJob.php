<?php

namespace App\Domain\Assistant\Jobs;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class NotifyOrphanedEventReferenceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $eventId,
        public readonly string $referenceableClass,
        public readonly int|string $referenceableId,
        public readonly string $reason,
    ) {}

    public static function dispatch(AssistantEvent $event, Model $referenceable, string $reason): void
    {
        static::dispatchSync($event->id, $referenceable::class, $referenceable->id, $reason);
    }

    public function handle(): void
    {
        $event = AssistantEvent::find($this->eventId);
        if (! $event) {
            return;
        }

        $conversation = Conversation::where('user_id', $event->user_id)->first();
        if (! $conversation) {
            return;
        }

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->first();

        if (! $session) {
            return;
        }

        $actions = [
            ['label' => 'Cancelar recordatorio', 'value' => "cancel_event:{$event->id}"],
            ['label' => 'Mantener sin referencia', 'value' => "detach_event_ref:{$event->id}"],
        ];

        $message = AssistantMessage::create([
            'conversation_id'  => $conversation->id,
            'session_id'       => $session->id,
            'role'             => 'system',
            'channel'          => 'web',
            'content'          => "El elemento vinculado al recordatorio \"{$event->content}\" fue eliminado.",
            'actions_json'     => $actions,
            'memory_processed' => false,
            'metadata_json'    => ['event_id' => $event->id],
            'created_at'       => now(),
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
            Log::error('assistant.orphaned_ref_broadcast_failed', ['exception' => $e]);
        }
    }
}
