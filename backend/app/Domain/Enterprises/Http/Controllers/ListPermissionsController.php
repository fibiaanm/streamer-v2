<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterprisePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListPermissionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $permissions = EnterprisePermission::orderBy('name')->pluck('name');

            return ResponseFormatter::success($permissions->values()->all());

        } catch (Throwable $e) {
            Log::error('enterprises.list_permissions_unexpected', ['exception' => $e->getMessage()]);
            return ResponseFormatter::serverError();
        }
    }
}
