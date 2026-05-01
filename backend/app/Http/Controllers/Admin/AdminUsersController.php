<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\TokenUsage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $users = User::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->withCount(['tokenUsages as total_tokens' => function ($q) use ($request) {
                $q->select(DB::raw('COALESCE(SUM(input_tokens + output_tokens), 0)'))
                  ->when($request->input('from'), fn ($q, $v) => $q->where('created_at', '>=', $v))
                  ->when($request->input('to'),   fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'));
            }])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'is_admin'     => $u->is_admin,
                'created_at'   => $u->created_at?->toISOString(),
                'total_tokens' => (int) $u->total_tokens,
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

        $usageQuery = TokenUsage::where('user_id', $user->id)
            ->when($request->input('from'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->input('to'),   fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'));

        $totals = (clone $usageQuery)->selectRaw('
            COALESCE(SUM(input_tokens), 0)  AS total_input,
            COALESCE(SUM(output_tokens), 0) AS total_output,
            COUNT(DISTINCT conversation_id) AS total_conversations
        ')->first();

        $byModel = (clone $usageQuery)
            ->select('model', DB::raw('SUM(input_tokens + output_tokens) AS total'))
            ->groupBy('model')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['model' => $r->model, 'total' => (int) $r->total]);

        return ResponseFormatter::success([
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'is_admin'            => $user->is_admin,
            'created_at'          => $user->created_at?->toISOString(),
            'total_input'         => (int) $totals->total_input,
            'total_output'        => (int) $totals->total_output,
            'total_conversations' => (int) $totals->total_conversations,
            'usage_by_model'      => $byModel,
        ]);
    }
}
