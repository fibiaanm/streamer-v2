<?php

namespace Database\Seeders;

use App\Models\WorkspacePermission;
use Illuminate\Database\Seeder;

class WorkspacePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Workspace general
            'workspace.view',
            'workspace.edit',
            'workspace.delete',
            'workspace.create_child',

            // Members
            'workspace.members.add',
            'workspace.members.delete',
            'workspace.members.change_role',

            // Roles
            'workspace.roles.add',
            'workspace.roles.edit',
            'workspace.roles.delete',

            // Assets
            'asset.upload',
            'asset.rename',
            'asset.move',
            'asset.delete',

            // Rooms
            'room.create',
            'room.manage',
        ];

        foreach ($permissions as $name) {
            WorkspacePermission::firstOrCreate(['name' => $name]);
        }
    }
}
