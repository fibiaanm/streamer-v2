<?php

namespace App\Domain\Workspaces\Gates;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

class WorkspacePermissionGate
{
    public function authorize(User $user, Workspace $workspace, string $permission): WorkspaceMember
    {
        $member = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->with('role.permissions')
            ->first();

        if (!$member) {
            throw new WorkspaceForbiddenException();
        }

        if (!$member->role->permissions->pluck('name')->contains($permission)) {
            throw new WorkspaceForbiddenException();
        }

        return $member;
    }
}
