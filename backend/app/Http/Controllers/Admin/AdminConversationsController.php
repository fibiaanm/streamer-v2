<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminConversationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => 'nullable|integer|exists:users,id',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $conversations = Conversation::query()
            ->join('users', 'assistant_conversations.user_id', '=', 'users.id')
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('assistant_conversations.user_id', $v))
            ->when($request->input('from'),    fn ($q, $v) => $q->where('assistant_conversations.created_at', '>=', $v))
            ->when($request->input('to'),      fn ($q, $v) => $q->where('assistant_conversations.created_at', '<=', $v . ' 23:59:59'))
            ->select(
                'assistant_conversations.id',
                'users.name AS user_name',
                'users.email AS user_email',
                'assistant_conversations.user_id',
                'assistant_conversations.created_at',
                DB::raw('(SELECT COUNT(*) FROM assistant_messages WHERE assistant_messages.conversation_id = assistant_conversations.id) AS message_count'),
                DB::raw('(SELECT COALESCE(SUM(input_tokens + output_tokens), 0) FROM token_usages WHERE token_usages.conversation_id = assistant_conversations.id) AS total_tokens'),
            )
            ->orderByDesc('assistant_conversations.created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'data' => $conversations->map(fn ($c) => [
                'id'            => $c->id,
                'user_id'       => $c->user_id,
                'user_name'     => $c->user_name,
                'user_email'    => $c->user_email,
                'message_count' => (int) $c->message_count,
                'total_tokens'  => (int) $c->total_tokens,
                'created_at'    => $c->created_at?->toISOString(),
            ]),
            'meta' => ['pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'total'        => $conversations->total(),
            ]],
        ]);
    }
}
