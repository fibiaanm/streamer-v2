<?php

namespace App\Domain\Workspaces\Application\UseCases;

use App\Domain\Workspaces\Gates\WorkspaceLimitsGate;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Models\Enterprise;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use App\Values\ResolvedLimits;
use Illuminate\Support\Facades\DB;

class CreateWorkspaceUseCase
{
    public function __construct(
        private readonly WorkspacePermissionGate $permissionGate,
        private readonly WorkspaceLimitsGate     $limitsGate,
    ) {}

    public function execute(
        User          $user,
        Enterprise    $enterprise,
        ResolvedLimits $limits,
        string        $name,
        ?Workspace    $parent = null,
    ): Workspace {
        if ($parent !== null) {
            $this->permissionGate->authorize($user, $parent, 'workspace.create_child');
        }

        $this->limitsGate->checkCreate($user, $limits, $parent);

        $workspace = DB::transaction(function () use ($user, $enterprise, $name, $parent) {
            $workspace = Workspace::create([
                'enterprise_id'       => $enterprise->id,
                'owner_user_id'       => $user->id,
                'parent_workspace_id' => $parent?->id,
                'name'                => $name,
                'status'              => 'active',
                'path'                => '',
            ]);

            $workspace->path = $parent === null
                ? (string) $workspace->id
                : $parent->path . '.' . $workspace->id;

            $workspace->save();

            return $workspace;
        });

        $this->seedRolesAndMembers($workspace, $user, $parent);

        return $workspace;
    }

    private function seedRolesAndMembers(Workspace $workspace, User $creator, ?Workspace $parent): void
    {
        $now = now();

        // Global base roles always included
        $globalRoles = WorkspaceRole::whereNull('workspace_id')
            ->whereIn('name', ['owner', 'admin', 'editor', 'viewer'])
            ->with('permissions:id')
            ->get();

        // Custom roles from parent (only for child workspaces)
        $parentCustomRoles = $parent !== null
            ? WorkspaceRole::where('workspace_id', $parent->id)
                ->where('is_base', false)
                ->with('permissions:id')
                ->get()
            : collect();

        $allRoles = $globalRoles->merge($parentCustomRoles);

        // Bulk insert roles
        WorkspaceRole::insert(
            $allRoles->map(fn ($r) => [
                'workspace_id' => $workspace->id,
                'name'         => $r->name,
                'is_base'      => $r->is_base,
                'created_at'   => $now,
            ])->all()
        );

        // Map name → local role id
        $localRoles = WorkspaceRole::where('workspace_id', $workspace->id)
            ->pluck('id', 'name');

        // Bulk insert permissions
        $permissionsData = [];
        foreach ($allRoles as $role) {
            $localId = $localRoles[$role->name] ?? null;
            if (!$localId || $role->permissions->isEmpty()) {
                continue;
            }
            foreach ($role->permissions as $permission) {
                $permissionsData[] = ['role_id' => $localId, 'permission_id' => $permission->id];
            }
        }

        if (!empty($permissionsData)) {
            DB::table('workspace_role_permissions')->insert($permissionsData);
        }

        // Build members list
        $membersData = [];

        // Copy parent members (excluding creator, handled separately)
        if ($parent !== null) {
            $parentMembers = WorkspaceMember::where('workspace_id', $parent->id)
                ->where('user_id', '!=', $creator->id)
                ->with('role:id,name')
                ->get();

            foreach ($parentMembers as $member) {
                $localId = $localRoles[$member->role->name] ?? null;
                if (!$localId) {
                    continue;
                }
                $membersData[] = [
                    'user_id'      => $member->user_id,
                    'workspace_id' => $workspace->id,
                    'role_id'      => $localId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        // Creator always gets owner role
        $ownerRoleId = $localRoles['owner'] ?? null;
        if ($ownerRoleId) {
            $membersData[] = [
                'user_id'      => $creator->id,
                'workspace_id' => $workspace->id,
                'role_id'      => $ownerRoleId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if (!empty($membersData)) {
            WorkspaceMember::insert($membersData);
        }
    }
}
