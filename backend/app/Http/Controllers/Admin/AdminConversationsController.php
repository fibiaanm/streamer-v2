<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\AdminSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminConversationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => 'nullable|integer',
            'email'    => 'nullable|email',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $hasFilter = $request->filled('user_id')
            || $request->filled('email')
            || $request->filled('from')
            || $request->filled('to');

        if (! $hasFilter) {
            return response()->json(['data' => [], 'meta' => ['pagination' => null]]);
        }

        $sessions = DB::table('assistant_sessions AS s')
            ->join('assistant_conversations AS c', 'c.id', '=', 's.conversation_id')
            ->join('users AS u', 'u.id', '=', 'c.user_id')
            ->when($request->filled('user_id'), fn ($q) => $q->where('c.user_id', $request->integer('user_id')))
            ->when($request->filled('email'),   fn ($q) => $q->whereRaw('LOWER(u.email) = ?', [strtolower($request->input('email'))]))
            ->when($request->filled('from'),    fn ($q) => $q->where('s.started_at', '>=', $request->input('from')))
            ->when($request->filled('to'),      fn ($q) => $q->where('s.started_at', '<=', $request->input('to') . ' 23:59:59'))
            ->select(
                's.id',
                's.title',
                's.started_at AS created_at',
                's.last_message_at',
                's.metadata_json',
                'c.user_id',
                'u.name AS user_name',
                'u.email AS user_email',
            )
            ->orderByDesc('s.started_at')
            ->simplePaginate($request->input('per_page', 25));

        return response()->json([
            'data' => $sessions->map(fn ($s) => (new AdminSessionResource($s))->toArray(request())),
            'meta' => ['pagination' => [
                'current_page' => $sessions->currentPage(),
                'has_more'     => $sessions->hasMorePages(),
            ]],
        ]);
    }
}
