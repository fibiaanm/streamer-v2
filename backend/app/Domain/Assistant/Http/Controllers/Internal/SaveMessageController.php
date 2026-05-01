<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SaveMessageController
{
    public function __invoke(Request $request, int $sessionId): JsonResponse
    {
        $request->validate([
            'role'              => 'required|string|in:assistant,tool_call,tool_result,tool_summary',
            'content'           => 'required|string',
            'options'           => 'nullable|array',
            'options.*.type'    => 'required_with:options|string|in:button,datetime',
            'options.*.label'   => 'required_with:options|string',
            'options.*.value'   => 'required_with:options|string',
            'options.*.default' => 'nullable|string',
        ]);

        $session = AssistantSession::findOrFail($sessionId);

        Log::info('assistant.save_message', [
            'session_id'      => $session->id,
            'conversation_id' => $session->conversation_id,
            'role'            => $request->input('role'),
        ]);

        $requestId = $request->header('X-Request-Id');

        $meta = array_filter([
            'request_id' => $requestId ?: null,
            'options'    => $request->input('options'),
        ]);

        $message = AssistantMessage::create([
            'conversation_id'  => $session->conversation_id,
            'session_id'       => $session->id,
            'role'             => $request->input('role'),
            'channel'          => 'web',
            'content'          => $request->input('content'),
            'memory_processed' => false,
            'metadata_json'    => $meta ?: null,
            'created_at'       => now(),
        ]);

        $session->incrementMessageCount();

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
                            'metadata'   => $message->metadata_json ?? [],
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
