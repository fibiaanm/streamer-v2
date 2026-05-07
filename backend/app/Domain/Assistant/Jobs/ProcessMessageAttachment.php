<?php

namespace App\Domain\Assistant\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMessageAttachment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $messageId,
        public readonly int $sessionId,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        // Audio transcription, image description, and PDF extraction
        // will be implemented in etapa 03 with LLM/Whisper integrations.

        ProcessAssistantMessage::dispatch(
            messageId: $this->messageId,
            sessionId: $this->sessionId,
            userId:    $this->userId,
        )->onQueue('assistant');
    }
}
