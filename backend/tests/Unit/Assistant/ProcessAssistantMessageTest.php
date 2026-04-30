<?php

use App\Domain\Assistant\Jobs\ProcessAssistantMessage;
use Illuminate\Support\Facades\Redis;

it('pushes a process_message payload to the assistant:jobs redis key', function () {
    Redis::shouldReceive('lpush')
        ->once()
        ->withArgs(function (string $key, string $payload): bool {
            $data = json_decode($payload, true);

            return $key === 'assistant:jobs'
                && $data['type'] === 'process_message'
                && isset($data['conversation_id'])
                && isset($data['message_id'])
                && isset($data['user_id']);
        });

    $job = new ProcessAssistantMessage(
        messageId:      1,
        conversationId: 10,
        userId:         5,
    );

    $job->handle();
});
