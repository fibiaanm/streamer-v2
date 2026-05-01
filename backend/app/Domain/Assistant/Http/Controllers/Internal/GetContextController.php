<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Memory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetContextController
{
    public function __invoke(Request $request, int $sessionId): JsonResponse
    {
        $session = AssistantSession::with('conversation.user')->findOrFail($sessionId);
        $user    = $session->conversation->user;

        Log::info('assistant.get_context', [
            'session_id' => $sessionId,
            'user_id'    => $user->id,
        ]);

        $messages = AssistantMessage::where('session_id', $sessionId)
            ->whereIn('role', ['user', 'assistant', 'tool_summary'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'      => $m->getHashId(),
                'role'    => $m->role,
                'content' => $m->content,
            ])
            ->values();

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
