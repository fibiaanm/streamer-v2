<?php

namespace Database\Seeders;

use App\Models\WorkspacePermission;
use App\Models\WorkspaceRole;
use Illuminate\Database\Seeder;

class WorkspaceRoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = WorkspacePermission::pluck('id', 'name');

        $roles = [
            'owner' => [
                'workspace.view',
                'workspace.edit',
                'workspace.delete',
                'workspace.create_child',
                'workspace.members.view',
                'workspace.members.add',
                'workspace.members.delete',
                'workspace.members.change_role',
                'workspace.roles.view',
                'workspace.roles.add',
                'workspace.roles.edit',
                'workspace.roles.delete',
                'asset.upload',
                'asset.rename',
                'asset.move',
                'asset.delete',
                'room.create',
                'room.manage',
            ],
            'admin' => [
                'workspace.view',
                'workspace.edit',
                'workspace.create_child',
                'workspace.members.view',
                'workspace.members.add',
                'workspace.members.delete',
                'workspace.members.change_role',
                'workspace.roles.view',
                'workspace.roles.add',
                'workspace.roles.edit',
                'workspace.roles.delete',
                'asset.upload',
                'asset.rename',
                'asset.move',
                'asset.delete',
                'room.create',
                'room.manage',
            ],
            'editor' => [
                'workspace.view',
                'workspace.edit',
                'workspace.create_child',
                'workspace.members.view',
                'asset.upload',
                'asset.rename',
                'asset.move',
                'asset.delete',
                'room.create',
            ],
            'viewer' => [
                'workspace.view',
                'workspace.members.view',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = WorkspaceRole::firstOrCreate(
                ['name' => $name, 'workspace_id' => null],
                ['is_base' => true],
            );

            $role->permissions()->sync(
                collect($permissions)->map(fn ($p) => $all[$p])->filter()->values()->all(),
            );
        }
    }
}
