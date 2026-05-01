<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SaveMessageController
{
    public function __invoke(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'role'    => 'required|string|in:assistant,tool_call,tool_result',
            'content' => 'required|string',
        ]);

        $conversation = Conversation::findOrFail($conversationId);

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->firstOrFail();

        Log::info('assistant.save_message', [
            'conversation_id' => $conversation->id,
            'session_id'      => $session->id,
            'role'            => $request->input('role'),
        ]);

        $requestId = $request->header('X-Request-Id');

        $message = AssistantMessage::create([
            'conversation_id'  => $conversation->id,
            'session_id'       => $session->id,
            'role'             => $request->input('role'),
            'channel'          => 'web',
            'content'          => $request->input('content'),
            'memory_processed' => false,
            'metadata_json'    => $requestId ? ['request_id' => $requestId] : null,
            'created_at'       => now(),
        ]);

        $session->update(['last_message_at' => now()]);

        if ($request->input('role') === 'assistant') {
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
                            'actions'    => [],
                            'metadata'   => [],
                            'created_at' => $message->created_at?->toISOString(),
                        ],
                    ],
                ]));
            } catch (Throwable $e) {
                Log::error('assistant.message_received_publish_failed', ['exception' => $e]);
            }
        }

        return response()->json(['data' => ['id' => $message->getHashId()]]);
    }
}
