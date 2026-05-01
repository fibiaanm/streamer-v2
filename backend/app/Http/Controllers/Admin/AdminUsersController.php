<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\TokenUsage;
use App\Http\Formatters\ResponseFormatter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'id'    => 'nullable|integer',
            'email' => 'nullable|email',
        ]);

        if (! $request->filled('id') && ! $request->filled('email')) {
            return response()->json(['data' => [], 'meta' => ['pagination' => null]]);
        }

        $users = User::query()
            ->when($request->filled('id'),    fn ($q) => $q->where('id', $request->integer('id')))
            ->when($request->filled('email'), fn ($q) => $q->whereRaw('LOWER(email) = ?', [strtolower($request->input('email'))]))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'is_admin'   => $u->is_admin,
                'created_at' => $u->created_at?->toISOString(),
            ]),
            'meta' => ['pagination' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ]],
        ]);
    }

    public function show(int $userId, Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);

        $user = User::findOrFail($userId);

        $usageQuery = DB::table('token_usage_user_daily')
            ->where('user_id', $user->id)
            ->when($request->input('from'), fn ($q, $v) => $q->where('date', '>=', $v))
            ->when($request->input('to'),   fn ($q, $v) => $q->where('date', '<=', $v));

        $totals = (clone $usageQuery)->selectRaw('
            COALESCE(SUM(input_tokens), 0)  AS total_input,
            COALESCE(SUM(output_tokens), 0) AS total_output,
            COALESCE(SUM(record_count), 0)  AS total_requests
        ')->first();

        $byModel = DB::table('token_usage_daily AS d')
            ->join('token_usage_user_daily AS ud', function ($join) use ($user) {
                $join->on('ud.date', '=', 'd.date')
                     ->where('ud.user_id', $user->id);
            })
            ->when($request->input('from'), fn ($q, $v) => $q->where('d.date', '>=', $v))
            ->when($request->input('to'),   fn ($q, $v) => $q->where('d.date', '<=', $v))
            ->select('d.model', DB::raw('SUM(d.input_tokens + d.output_tokens) AS total'))
            ->groupBy('d.model')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['model' => $r->model, 'total' => (int) $r->total]);

        return ResponseFormatter::success([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'is_admin'       => $user->is_admin,
            'created_at'     => $user->created_at?->toISOString(),
            'total_input'    => (int) $totals->total_input,
            'total_output'   => (int) $totals->total_output,
            'total_requests' => (int) $totals->total_requests,
            'usage_by_model' => $byModel,
        ]);
    }
}
