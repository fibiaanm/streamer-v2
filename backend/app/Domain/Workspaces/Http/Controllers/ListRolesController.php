<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceRoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspaceRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListRolesController
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

            $member = $this->permissionGate->authorize($request->user(), $workspace, 'workspace.view');

            $roles = WorkspaceRole::where('workspace_id', $workspace->id)
                ->with('permissions')
                ->get();

            if ($request->boolean('assignable')) {
                $member->loadMissing('role.permissions');
                $myPerms = $member->role->permissions->pluck('name')->flip();
                $roles   = $roles->filter(
                    fn ($role) => $role->name !== 'owner'
                        && $role->permissions->every(fn ($p) => $myPerms->has($p->name))
                )->values();
            }

            return ResponseFormatter::success(WorkspaceRoleResource::collection($roles));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.list_roles_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
