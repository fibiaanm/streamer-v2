<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Exceptions\WorkspaceRoleBaseImmutableException;
use App\Domain\Workspaces\Exceptions\WorkspaceRoleHasMembersException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteRoleController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId, string $roleId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.roles.delete');

            $id   = HashId::decode($roleId);
            $role = $id ? WorkspaceRole::find($id) : null;

            if (!$role || $role->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            if ($role->is_base) {
                throw new WorkspaceRoleBaseImmutableException();
            }

            $hasMembers = WorkspaceMember::where('workspace_id', $workspace->id)
                ->where('role_id', $role->id)
                ->exists();

            if ($hasMembers) {
                throw new WorkspaceRoleHasMembersException();
            }

            $role->permissions()->detach();
            $role->delete();

            return ResponseFormatter::noContent();

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException | WorkspaceRoleBaseImmutableException | WorkspaceRoleHasMembersException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.delete_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
