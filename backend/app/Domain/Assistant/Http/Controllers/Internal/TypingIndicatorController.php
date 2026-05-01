<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class TypingIndicatorController
{
    public function __invoke(Request $request, int $sessionId): JsonResponse
    {
        $session = AssistantSession::findOrFail($sessionId);

        Log::info('assistant.typing_indicator', [
            'session_id'      => $session->id,
            'conversation_id' => $session->conversation_id,
        ]);

        try {
            Redis::connection('pubsub')->publish("assistant.{$session->getHashId()}", json_encode([
                'event' => 'MessageProcessing',
                'data'  => ['sessionId' => $session->getHashId()],
            ]));
        } catch (Throwable $e) {
            Log::error('assistant.typing_publish_failed', ['exception' => $e]);
        }

        return response()->json(['ok' => true]);
    }
}
