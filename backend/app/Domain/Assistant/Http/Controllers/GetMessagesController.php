<?php

namespace App\Domain\Assistant\Http\Controllers;

use App\Domain\Assistant\Http\Resources\AssistantMessageResource;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class GetMessagesController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'session' => ['required', 'string'],
        ]);

        $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);

        $session = AssistantSession::findByHashId($request->input('session'));

        if (!$session || $session->conversation_id !== $conversation->id) {
            throw new NotFoundHttpException();
        }

        try {
            $messages = AssistantMessage::where('session_id', $session->id)
                ->whereNotIn('role', ['tool_call', 'tool_result'])
                ->orderBy('created_at', 'asc')
                ->paginate(30);

            return ResponseFormatter::paginated($messages, AssistantMessageResource::class);

        } catch (Throwable $e) {
            Log::error('assistant.get_messages_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
