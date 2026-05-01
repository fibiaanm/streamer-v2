<?php

namespace App\Http\Controllers\Admin;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageTimelineController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week',
            'provider' => 'nullable|string',
            'model'    => 'nullable|string',
            'type'     => 'nullable|string|in:text,image,embedding,memory,audio',
        ]);

        $groupBy = $request->input('group_by', 'day');

        $dateTrunc = $groupBy === 'week'
            ? "DATE_TRUNC('week', date)"
            : 'date';

        $rows = DB::table('token_usage_daily')
            ->whereBetween('date', [$request->input('from'), $request->input('to')])
            ->when($request->input('provider'), fn ($q, $v) => $q->where('provider', $v))
            ->when($request->input('model'),    fn ($q, $v) => $q->where('model', $v))
            ->when($request->input('type'),     fn ($q, $v) => $q->where('type', $v))
            ->selectRaw("{$dateTrunc} AS bucket, COALESCE(SUM(input_tokens),0) AS input_tokens, COALESCE(SUM(output_tokens),0) AS output_tokens")
            ->groupBy(DB::raw($dateTrunc))
            ->orderBy(DB::raw($dateTrunc))
            ->get()
            ->map(fn ($r) => [
                'date'          => substr($r->bucket, 0, 10),
                'input_tokens'  => (int) $r->input_tokens,
                'output_tokens' => (int) $r->output_tokens,
            ])
            ->all();

        return ResponseFormatter::success($rows);
    }
}
