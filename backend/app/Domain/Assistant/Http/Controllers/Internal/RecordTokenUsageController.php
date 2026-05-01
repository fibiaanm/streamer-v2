<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\TokenUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordTokenUsageController
{
    public function __invoke(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'type'          => 'required|string|in:text,image,embedding,memory,audio',
            'provider'      => 'required|string|max:30',
            'model'         => 'required|string|max:80',
            'input_tokens'  => 'nullable|integer|min:0',
            'output_tokens' => 'nullable|integer|min:0',
            'units'         => 'nullable|integer|min:0',
            'iteration'     => 'integer|min:1|max:255',
        ]);

        $conversation = Conversation::findOrFail($conversationId);

        $session = AssistantSession::where('conversation_id', $conversation->id)
            ->latest('last_message_at')
            ->firstOrFail();

        TokenUsage::create([
            'user_id'         => $conversation->user_id,
            'conversation_id' => $conversation->id,
            'session_id'      => $session->id,
            'type'            => $request->input('type'),
            'provider'        => $request->input('provider'),
            'model'           => $request->input('model'),
            'input_tokens'    => $request->input('input_tokens'),
            'output_tokens'   => $request->input('output_tokens'),
            'units'           => $request->input('units'),
            'iteration'       => $request->input('iteration', 1),
            'request_id'      => $request->header('X-Request-Id'),
            'created_at'      => now(),
        ]);

        Log::info('assistant.token_usage_recorded', [
            'conversation_id' => $conversation->id,
            'type'            => $request->input('type'),
            'model'           => $request->input('model'),
            'input_tokens'    => $request->input('input_tokens'),
            'output_tokens'   => $request->input('output_tokens'),
        ]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
