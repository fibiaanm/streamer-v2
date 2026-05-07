<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id'       => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $jobs = DB::table('jobs')
            ->when($request->filled('id'), fn ($q) => $q->where('id', $request->integer('id')))
            ->orderByDesc('id')
            ->simplePaginate($request->input('per_page', 25));

        return response()->json([
            'data' => $jobs->map(fn ($j) => [
                'id'           => $j->id,
                'queue'        => $j->queue,
                'display_name' => data_get(json_decode($j->payload, true), 'displayName', 'Unknown'),
                'attempts'     => $j->attempts,
                'available_at' => $j->available_at,
                'created_at'   => $j->created_at,
            ])->all(),
            'meta' => ['pagination' => [
                'current_page' => $jobs->currentPage(),
                'has_more'     => $jobs->hasMorePages(),
            ]],
        ]);
    }
}
