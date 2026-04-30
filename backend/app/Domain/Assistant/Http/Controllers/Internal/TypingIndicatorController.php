<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class TypingIndicatorController
{
    public function __invoke(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->first();

        if ($session) {
            Log::info('assistant.typing_indicator', [
                'conversation_id' => $conversationId,
                'session_id'      => $session->id,
            ]);
            try {
                Redis::connection('pubsub')->publish("assistant.{$session->getHashId()}", json_encode([
                    'event' => 'MessageProcessing',
                    'data'  => ['sessionId' => $session->getHashId()],
                ]));
            } catch (Throwable $e) {
                Log::error('assistant.typing_publish_failed', ['exception' => $e]);
            }
        } else {
            Log::warning('assistant.typing_no_session', ['conversation_id' => $conversationId]);
        }

        return response()->json(['ok' => true]);
    }
}
