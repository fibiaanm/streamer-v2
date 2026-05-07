<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFailedJobsSummaryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'  => 'required|date',
            'to'    => 'required|date|after_or_equal:from',
            'queue' => 'nullable|string',
        ]);

        $from = $request->input('from');
        $to   = $request->input('to') . ' 23:59:59';

        $base = DB::table('failed_jobs')
            ->whereBetween('failed_at', [$from, $to])
            ->when($request->filled('queue'), fn ($q) => $q->where('queue', $request->input('queue')));

        $total = (clone $base)->count();

        $lastFailedAt = (clone $base)->latest('failed_at')->value('failed_at');

        $mostFailingPayload = (clone $base)
            ->select('payload', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('payload')
            ->orderByDesc('cnt')
            ->value('payload');

        $mostFailingJob = $mostFailingPayload
            ? data_get(json_decode($mostFailingPayload, true), 'displayName')
            : null;

        $byQueue = DB::table('failed_jobs')
            ->whereBetween('failed_at', [$from, $to])
            ->select('queue', DB::raw('COUNT(*) AS count'))
            ->groupBy('queue')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['queue' => $r->queue, 'count' => (int) $r->count])
            ->all();

        $queues = collect($byQueue)->pluck('queue')->all();

        return response()->json([
            'data' => [
                'total'            => $total,
                'last_failed_at'   => $lastFailedAt,
                'most_failing_job' => $mostFailingJob,
                'by_queue'         => $byQueue,
                'queues'           => $queues,
            ],
        ]);
    }
}
