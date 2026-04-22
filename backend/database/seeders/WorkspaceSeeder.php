<?php

namespace Database\Seeders;

use App\Models\EnterpriseMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkspaceSeeder extends Seeder
{
    private const WORKSPACE_COUNT = 40;

    public function run(): void
    {
        $teamsUser = User::where('email', 'teams@test.com')->firstOrFail();
        $adminUser = User::where('email', 'admin@teams.test')->firstOrFail();

        $enterprise = EnterpriseMember::where('user_id', $teamsUser->id)
            ->with('enterprise')
            ->firstOrFail()
            ->enterprise;

        $globalRoles = WorkspaceRole::whereNull('workspace_id')
            ->whereIn('name', ['owner', 'admin', 'editor', 'viewer'])
            ->with('permissions:id')
            ->get();

        $now = now();

        for ($i = 1; $i <= self::WORKSPACE_COUNT; $i++) {
            $workspace = Workspace::create([
                'enterprise_id'       => $enterprise->id,
                'owner_user_id'       => $teamsUser->id,
                'parent_workspace_id' => null,
                'name'                => "Workspace $i",
                'status'              => 'active',
                'path'                => '',
            ]);

            $workspace->path = (string) $workspace->id;
            $workspace->save();

            // Seed local roles from global base roles
            WorkspaceRole::insert(
                $globalRoles->map(fn ($r) => [
                    'workspace_id' => $workspace->id,
                    'name'         => $r->name,
                    'is_base'      => true,
                    'created_at'   => $now,
                ])->all()
            );

            $localRoles = WorkspaceRole::where('workspace_id', $workspace->id)
                ->pluck('id', 'name');

            // Seed role permissions
            $permissionsData = [];
            foreach ($globalRoles as $role) {
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

            // Add teams user as owner, admin@teams as admin
            WorkspaceMember::insert([
                [
                    'workspace_id' => $workspace->id,
                    'user_id'      => $teamsUser->id,
                    'role_id'      => $localRoles['owner'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'workspace_id' => $workspace->id,
                    'user_id'      => $adminUser->id,
                    'role_id'      => $localRoles['admin'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);
        }
    }
}
