<?php

use App\Domain\Assistant\Jobs\ProcessAssistantMessage;
use App\Domain\Assistant\Jobs\ProcessMessageAttachment;
use Illuminate\Support\Facades\Bus;

it('dispatches ProcessAssistantMessage after attachment processing completes', function () {
    Bus::fake();

    $job = new ProcessMessageAttachment(
        messageId:      1,
        conversationId: 10,
        userId:         5,
    );

    $job->handle();

    Bus::assertDispatched(ProcessAssistantMessage::class, function ($dispatched) {
        return $dispatched->messageId === 1
            && $dispatched->conversationId === 10
            && $dispatched->userId === 5;
    });
});
