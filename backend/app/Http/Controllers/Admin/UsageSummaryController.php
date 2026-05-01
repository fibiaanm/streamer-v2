<?php

namespace App\Http\Controllers\Admin;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageSummaryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'provider' => 'nullable|string',
            'model'    => 'nullable|string',
            'type'     => 'nullable|string|in:text,image,embedding,memory,audio',
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        $query = DB::table('token_usage_daily')
            ->whereBetween('date', [$from, $to])
            ->when($request->input('provider'), fn ($q, $v) => $q->where('provider', $v))
            ->when($request->input('model'),    fn ($q, $v) => $q->where('model', $v))
            ->when($request->input('type'),     fn ($q, $v) => $q->where('type', $v));

        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(input_tokens), 0)  AS total_input,
            COALESCE(SUM(output_tokens), 0) AS total_output,
            COALESCE(SUM(input_tokens + output_tokens), 0) AS total_tokens,
            COALESCE(SUM(record_count), 0) AS total_records,
            COALESCE(SUM(CASE WHEN type = \'memory\' THEN input_tokens + output_tokens ELSE 0 END), 0) AS memory_tokens
        ')->first();

        $topModel = (clone $query)
            ->select('model', DB::raw('SUM(input_tokens + output_tokens) AS total'))
            ->groupBy('model')
            ->orderByDesc('total')
            ->value('model');

        $totalConversations = DB::table('assistant_sessions')
            ->whereBetween('started_at', [$from, $to . ' 23:59:59'])
            ->count();

        $totalTokens = (int) $totals->total_tokens;
        $avgPerConversation = $totalConversations > 0
            ? (int) round($totalTokens / $totalConversations)
            : null;

        $state = DB::table('token_usage_rollup_state')->value('last_run_at');

        $memoryTokens = (int) $totals->memory_tokens;

        return ResponseFormatter::success([
            'total_input'          => (int) $totals->total_input,
            'total_output'         => (int) $totals->total_output,
            'total_tokens'         => $totalTokens,
            'total_records'        => (int) $totals->total_records,
            'total_conversations'  => $totalConversations,
            'avg_tokens_per_conv'  => $avgPerConversation,
            'memory_tokens'        => $memoryTokens,
            'top_model'            => $topModel,
            'last_run_at'          => $state,
        ]);
    }
}
