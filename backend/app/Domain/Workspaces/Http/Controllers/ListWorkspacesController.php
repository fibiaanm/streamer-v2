<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Http\Resources\WorkspaceResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListWorkspacesController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');
            $user       = $request->user();

            $workspaces = Workspace::where('enterprise_id', $enterprise->id)
                ->whereNull('parent_workspace_id')
                ->whereIn('id', function ($q) use ($user) {
                    $q->select('workspace_id')->from('workspace_members')->where('user_id', $user->id);
                })
                ->with('owner')
                ->get();

            return ResponseFormatter::success(WorkspaceResource::collection($workspaces));

        } catch (Throwable $e) {
            Log::error('workspaces.list_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
