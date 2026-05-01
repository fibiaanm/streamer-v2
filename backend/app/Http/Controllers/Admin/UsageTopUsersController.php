<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\TokenUsage;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageTopUsersController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'provider' => 'nullable|string',
            'model'    => 'nullable|string',
            'type'     => 'nullable|string|in:text,image,embedding,memory,audio',
            'limit'    => 'nullable|integer|min:1|max:100',
        ]);

        $rows = TokenUsage::query()
            ->join('users', 'token_usages.user_id', '=', 'users.id')
            ->whereBetween('token_usages.created_at', [$request->input('from'), $request->input('to') . ' 23:59:59'])
            ->when($request->input('provider'), fn ($q, $v) => $q->where('token_usages.provider', $v))
            ->when($request->input('model'),    fn ($q, $v) => $q->where('token_usages.model', $v))
            ->when($request->input('type'),     fn ($q, $v) => $q->where('token_usages.type', $v))
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(token_usages.input_tokens),0)  AS input_tokens'),
                DB::raw('COALESCE(SUM(token_usages.output_tokens),0) AS output_tokens'),
                DB::raw('COALESCE(SUM(token_usages.input_tokens + token_usages.output_tokens),0) AS total_tokens'),
                DB::raw('COUNT(DISTINCT token_usages.conversation_id) AS conversations'),
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_tokens')
            ->limit($request->input('limit', 20))
            ->get()
            ->map(fn ($r) => [
                'user_id'       => $r->id,
                'name'          => $r->name,
                'email'         => $r->email,
                'input_tokens'  => (int) $r->input_tokens,
                'output_tokens' => (int) $r->output_tokens,
                'total_tokens'  => (int) $r->total_tokens,
                'conversations' => (int) $r->conversations,
            ])
            ->all();

        return ResponseFormatter::success($rows);
    }
}
