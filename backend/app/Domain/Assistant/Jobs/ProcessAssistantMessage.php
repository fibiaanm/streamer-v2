<?php

namespace App\Domain\Assistant\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessAssistantMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $messageId,
        public readonly int $sessionId,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        Log::info('assistant.redis_push', [
            'session_id' => $this->sessionId,
            'message_id' => $this->messageId,
            'user_id'    => $this->userId,
        ]);

        Redis::connection('pubsub')->lpush('assistant:jobs', json_encode([
            'type'       => 'process_message',
            'session_id' => $this->sessionId,
            'message_id' => $this->messageId,
            'user_id'    => $this->userId,
            'request_id' => Context::get('request_id'),
        ]));
    }
}
