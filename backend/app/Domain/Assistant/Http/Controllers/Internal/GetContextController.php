<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Memory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetContextController
{
    public function __invoke(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        Log::info('assistant.get_context', [
            'user_id'          => $userId,
            'has_conversation' => Conversation::where('user_id', $userId)->exists(),
        ]);

        $conversation = Conversation::where('user_id', $user->id)->first();

        $messages = $conversation
            ? AssistantMessage::where('conversation_id', $conversation->id)
                ->whereIn('role', ['user', 'assistant', 'tool_call', 'tool_result'])
                ->where('memory_processed', false)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($m) => [
                    'id'      => $m->getHashId(),
                    'role'    => $m->role,
                    'content' => $m->content,
                ])
                ->values()
            : collect();

        $memories = Memory::where('user_id', $user->id)
            ->get()
            ->map(fn ($m) => [
                'category'    => $m->category,
                'description' => $m->description,
                'content'     => $m->content,
            ])
            ->values();

        return response()->json([
            'data' => [
                'user' => [
                    'name'            => $user->name,
                    'timezone'        => $user->timezone ?? 'UTC',
                    'defaultCurrency' => $user->default_currency ?? 'USD',
                    'planTier'        => 'free',
                ],
                'messages' => $messages,
                'memories' => $memories,
            ],
        ]);
    }
}
