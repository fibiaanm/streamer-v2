<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Http\Resources\RoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListRolesController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $roles = EnterpriseRole::where(function ($q) use ($enterprise) {
                $q->whereNull('enterprise_id')
                  ->orWhere('enterprise_id', $enterprise->id);
            })
            ->with('permissions')
            ->get();

            return ResponseFormatter::success(RoleResource::collection($roles));

        } catch (Throwable $e) {
            Log::error('enterprises.list_roles_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
