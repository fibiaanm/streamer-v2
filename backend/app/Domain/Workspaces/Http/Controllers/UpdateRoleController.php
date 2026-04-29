<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Events\WorkspaceRolePermissionsUpdated;
use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Exceptions\WorkspaceRoleBaseImmutableException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceRoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspacePermission;
use App\Models\WorkspaceRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateRoleController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId, string $roleId): JsonResponse
    {
        $request->validate([
            'name'          => ['sometimes', 'string', 'max:64'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.roles.edit');

            $id   = HashId::decode($roleId);
            $role = $id ? WorkspaceRole::find($id) : null;

            if (!$role || $role->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            if ($role->is_base) {
                throw new WorkspaceRoleBaseImmutableException();
            }

            if ($request->filled('name')) {
                $role->name = $request->input('name');
                $role->save();
            }

            if ($request->has('permissions')) {
                $ids = WorkspacePermission::whereIn('name', $request->input('permissions'))->pluck('id');
                $role->permissions()->sync($ids);
            }

            $role->load('permissions');

            event(new WorkspaceRolePermissionsUpdated($workspace, $role));

            return ResponseFormatter::success(new WorkspaceRoleResource($role));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException | WorkspaceRoleBaseImmutableException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.update_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
