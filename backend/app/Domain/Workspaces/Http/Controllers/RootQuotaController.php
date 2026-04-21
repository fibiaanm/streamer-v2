<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Services\LimitsResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RootQuotaController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user               = $request->user();
            $enterpriseProducts = $request->attributes->get('active_enterprise_products');
            $limits             = app(LimitsResolver::class)->resolve($enterpriseProducts);

            $used = Workspace::where('owner_user_id', $user->id)
                ->whereNull('parent_workspace_id')
                ->count();

            return ResponseFormatter::success([
                'used'  => $used,
                'limit' => $limits->maxWorkspaces(),
            ]);

        } catch (Throwable $e) {
            Log::error('workspaces.root_quota_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
