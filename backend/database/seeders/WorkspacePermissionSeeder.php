<?php

namespace Database\Seeders;

use App\Models\WorkspacePermission;
use Illuminate\Database\Seeder;

class WorkspacePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'workspace.view',
            'workspace.edit',
            'workspace.delete',
            'workspace.members.manage',
            'asset.upload',
            'asset.delete',
            'room.create',
            'room.manage',
        ];

        foreach ($permissions as $name) {
            WorkspacePermission::firstOrCreate(['name' => $name]);
        }
    }
}
