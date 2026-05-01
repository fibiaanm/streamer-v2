<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\TokenUsage;
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

        $query = TokenUsage::query()
            ->whereBetween('created_at', [$request->input('from'), $request->input('to') . ' 23:59:59'])
            ->when($request->input('provider'), fn ($q, $v) => $q->where('provider', $v))
            ->when($request->input('model'),    fn ($q, $v) => $q->where('model', $v))
            ->when($request->input('type'),     fn ($q, $v) => $q->where('type', $v));

        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(input_tokens), 0)  AS total_input,
            COALESCE(SUM(output_tokens), 0) AS total_output,
            COALESCE(SUM(input_tokens + output_tokens), 0) AS total_tokens,
            COUNT(DISTINCT conversation_id) AS total_conversations
        ')->first();

        $topModel = (clone $query)
            ->select('model', DB::raw('SUM(input_tokens + output_tokens) AS total'))
            ->groupBy('model')
            ->orderByDesc('total')
            ->value('model');

        return ResponseFormatter::success([
            'total_input'         => (int) $totals->total_input,
            'total_output'        => (int) $totals->total_output,
            'total_tokens'        => (int) $totals->total_tokens,
            'total_conversations' => (int) $totals->total_conversations,
            'top_model'           => $topModel,
        ]);
    }
}
