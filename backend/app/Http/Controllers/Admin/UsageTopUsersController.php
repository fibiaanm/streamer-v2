<?php

namespace App\Http\Controllers\Admin;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageTopUsersController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from'  => 'required|date',
            'to'    => 'required|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $rows = DB::table('token_usage_user_daily AS ud')
            ->join('users', 'ud.user_id', '=', 'users.id')
            ->whereBetween('ud.date', [$request->input('from'), $request->input('to')])
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(ud.input_tokens),0)  AS input_tokens'),
                DB::raw('COALESCE(SUM(ud.output_tokens),0) AS output_tokens'),
                DB::raw('COALESCE(SUM(ud.input_tokens + ud.output_tokens),0) AS total_tokens'),
                DB::raw('COALESCE(SUM(ud.record_count),0) AS requests'),
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
                'requests'      => (int) $r->requests,
            ])
            ->all();

        return ResponseFormatter::success($rows);
    }
}
