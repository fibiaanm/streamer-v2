<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetUnprocessedMessagesController
{
    public function __invoke(Request $request, string $userId): JsonResponse
    {
        $user = User::findByHashId($userId) ?? throw new NotFoundHttpException();

        $conversation = Conversation::where('user_id', $user->id)->first();

        if (! $conversation) {
            return response()->json(['data' => []]);
        }

        $messages = AssistantMessage::where('conversation_id', $conversation->id)
            ->where('memory_processed', false)
            ->get()
            ->map(fn ($m) => [
                'id'               => $m->getHashId(),
                'role'             => $m->role,
                'content'          => $m->content,
                'memory_processed' => $m->memory_processed,
            ]);

        return response()->json(['data' => $messages]);
    }
}
