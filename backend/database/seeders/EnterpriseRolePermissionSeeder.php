<?php

namespace Database\Seeders;

use App\Models\EnterprisePermission;
use App\Models\EnterpriseRole;
use Illuminate\Database\Seeder;

class EnterpriseRolePermissionSeeder extends Seeder
{
    /**
     * Asigna permisos a los roles globales de empresa.
     *
     * Regla: el rol 'owner' tiene TODOS los permisos y es inmutable.
     * Los endpoints de gestión de permisos deben validar que el rol owner
     * no puede perder permisos (ErrorCode::EnterpriseRoleBaseImmutable).
     */
    public function run(): void
    {
        $all = EnterprisePermission::pluck('id', 'name');

        $assignments = [
            'owner' => $all->keys()->all(),

            'admin' => [
                'enterprise.settings.view',
                'enterprise.settings.edit',
                'enterprise.members.view',
                'enterprise.members.invite',
                'enterprise.members.remove',
                'enterprise.roles.add',
                'enterprise.roles.edit',
                'enterprise.roles.remove',
                'enterprise.roles.assign',
                'enterprise.billing.view',
                'workspace.create',
                'workspace.delete',
                'workspace.members.invite_external',
            ],

            'member' => [
                'enterprise.settings.view',
                'enterprise.members.view',
                'workspace.create',
            ],

            'billing' => [
                'enterprise.settings.view',
                'enterprise.billing.view',
                'enterprise.billing.manage',
            ],
        ];

        foreach ($assignments as $roleName => $permissionNames) {
            $role = EnterpriseRole::where('name', $roleName)
                ->whereNull('enterprise_id')
                ->first();

            if (!$role) {
                continue;
            }

            $ids = collect($permissionNames)
                ->map(fn (string $name) => $all->get($name))
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }
    }
}
