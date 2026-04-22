<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AncestorsController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.view');

            $ancestors = Workspace::whereRaw('path @> ?::ltree', [$workspace->path])
                ->where('id', '!=', $workspace->id)
                ->where('enterprise_id', $enterprise->id)
                ->with('owner')
                ->orderByRaw('nlevel(path) ASC')
                ->get();

            return ResponseFormatter::success(WorkspaceResource::collection($ancestors));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.ancestors_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
