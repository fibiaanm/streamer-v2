<?php

namespace Database\Seeders;

use App\Models\EnterprisePermission;
use Illuminate\Database\Seeder;

class EnterprisePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'enterprise.settings.view',
            'enterprise.settings.edit',
            'enterprise.members.view',
            'enterprise.members.invite',
            'enterprise.members.remove',
            'enterprise.roles.manage',        // solo owner puede gestionar roles base
            'enterprise.billing.view',
            'enterprise.billing.manage',
            'workspace.create',
            'workspace.delete',
            'workspace.members.invite_external', // invitar usuarios externos a workspaces
        ];

        foreach ($permissions as $name) {
            EnterprisePermission::firstOrCreate(['name' => $name]);
        }
    }
}
