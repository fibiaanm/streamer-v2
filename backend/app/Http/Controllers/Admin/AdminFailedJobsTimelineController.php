<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFailedJobsTimelineController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week',
            'queue'    => 'nullable|string',
        ]);

        $groupBy   = $request->input('group_by', 'day');
        $dateTrunc = $groupBy === 'week' ? "DATE_TRUNC('week', failed_at)" : "DATE_TRUNC('day', failed_at)";

        $rows = DB::table('failed_jobs')
            ->whereBetween('failed_at', [$request->input('from'), $request->input('to') . ' 23:59:59'])
            ->when($request->filled('queue'), fn ($q) => $q->where('queue', $request->input('queue')))
            ->selectRaw("{$dateTrunc} AS bucket, queue, COUNT(*) AS count")
            ->groupBy(DB::raw($dateTrunc), 'queue')
            ->orderBy(DB::raw($dateTrunc))
            ->get()
            ->map(fn ($r) => [
                'date'  => substr($r->bucket, 0, 10),
                'queue' => $r->queue,
                'count' => (int) $r->count,
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }
}
