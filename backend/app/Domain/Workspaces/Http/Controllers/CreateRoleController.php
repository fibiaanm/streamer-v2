<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceRoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspacePermission;
use App\Models\WorkspaceRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateRoleController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:64'],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.roles.add');

            $role = WorkspaceRole::create([
                'workspace_id' => $workspace->id,
                'name'         => $request->input('name'),
                'is_base'      => false,
            ]);

            if ($request->filled('permissions')) {
                $ids = WorkspacePermission::whereIn('name', $request->input('permissions'))->pluck('id');
                $role->permissions()->sync($ids);
            }

            $role->load('permissions');

            return ResponseFormatter::created(new WorkspaceRoleResource($role));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.create_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
