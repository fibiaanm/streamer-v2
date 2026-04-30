<?php

namespace App\Domain\Assistant\Http\Controllers;

use App\Domain\Assistant\Http\Resources\ConversationResource;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetConversationController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);

            $activeSession = AssistantSession::where('conversation_id', $conversation->id)
                ->where('last_message_at', '>', now()->subHours(24))
                ->latest('last_message_at')
                ->first();

            return ResponseFormatter::success(new ConversationResource($conversation, $activeSession));

        } catch (Throwable $e) {
            Log::error('assistant.get_conversation_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
