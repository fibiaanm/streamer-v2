<?php

namespace App\Domain\Assistant\Http\Controllers;

use App\Domain\Assistant\Http\Resources\AssistantSessionResource;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetSessionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);

            $sessions = AssistantSession::where('conversation_id', $conversation->id)
                ->orderBy('started_at', 'desc')
                ->cursorPaginate(20);

            return ResponseFormatter::cursor($sessions, AssistantSessionResource::class);

        } catch (Throwable $e) {
            Log::error('assistant.get_sessions_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
