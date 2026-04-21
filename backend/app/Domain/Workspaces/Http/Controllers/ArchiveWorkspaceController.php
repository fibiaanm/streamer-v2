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
use Illuminate\Support\Facades\Log;
use Throwable;

class ArchiveWorkspaceController
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

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.delete');

            $workspace->update(['status' => 'archived']);
            $workspace->load('owner');

            return ResponseFormatter::success(new WorkspaceResource($workspace));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.archive_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
