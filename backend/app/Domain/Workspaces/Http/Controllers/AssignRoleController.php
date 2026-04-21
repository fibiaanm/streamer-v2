<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Events\WorkspaceMemberRoleChanged;
use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Exceptions\WorkspaceRoleBaseImmutableException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceMemberResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssignRoleController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId, string $memberId): JsonResponse
    {
        $request->validate(['role_id' => ['required', 'string']]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $currentMember = $this->permissionGate->authorize(
                $request->user(), $workspace, 'workspace.members.change_role'
            );

            $id     = HashId::decode($memberId);
            $target = $id ? WorkspaceMember::find($id) : null;

            if (!$target || $target->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            $target->loadMissing('role');
            if ($target->role->name === 'owner') {
                throw new WorkspaceRoleBaseImmutableException();
            }

            $roleId  = HashId::decode($request->input('role_id'));
            $newRole = $roleId ? WorkspaceRole::find($roleId) : null;

            if (!$newRole || $newRole->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            if ($newRole->name === 'owner') {
                throw new WorkspaceRoleBaseImmutableException();
            }

            // Subset check: new role's permissions must be ⊆ current user's permissions
            $currentMember->loadMissing('role.permissions');
            $newRole->loadMissing('permissions');
            $myPerms      = $currentMember->role->permissions->pluck('name')->all();
            $newRolePerms = $newRole->permissions->pluck('name')->all();

            if (!empty(array_diff($newRolePerms, $myPerms))) {
                throw new WorkspaceForbiddenException();
            }

            $target->role_id = $newRole->id;
            $target->save();
            $target->load(['user.media', 'role']);

            event(new WorkspaceMemberRoleChanged($workspace, $target, $newRole));

            return ResponseFormatter::success(new WorkspaceMemberResource($target));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException | WorkspaceRoleBaseImmutableException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.assign_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
