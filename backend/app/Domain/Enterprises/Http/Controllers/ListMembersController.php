<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Http\Resources\MemberResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListMembersController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $status  = $request->query('status', 'active');
            $members = EnterpriseMember::where('enterprise_id', $enterprise->id)
                ->where('status', $status)
                ->with(['user', 'role'])
                ->get();

            return ResponseFormatter::success(MemberResource::collection($members));

        } catch (Throwable $e) {
            Log::error('enterprises.list_members_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
