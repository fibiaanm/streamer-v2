<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove old aggregate permission from role_permissions pivot
        $old = DB::table('enterprise_permissions')->where('name', 'enterprise.roles.manage')->first();

        if ($old) {
            DB::table('enterprise_role_permissions')->where('permission_id', $old->id)->delete();
            DB::table('enterprise_permissions')->where('id', $old->id)->delete();
        }

        // Insert granular permissions
        $now  = now();
        $names = ['enterprise.roles.add', 'enterprise.roles.edit', 'enterprise.roles.remove', 'enterprise.roles.assign'];

        foreach ($names as $name) {
            DB::table('enterprise_permissions')->insertOrIgnore(['name' => $name]);
        }

        // Assign all four to owner and admin global roles
        $ownerRole  = DB::table('enterprise_roles')->whereNull('enterprise_id')->where('name', 'owner')->first();
        $adminRole  = DB::table('enterprise_roles')->whereNull('enterprise_id')->where('name', 'admin')->first();
        $newIds     = DB::table('enterprise_permissions')->whereIn('name', $names)->pluck('id');

        foreach (array_filter([$ownerRole, $adminRole]) as $role) {
            foreach ($newIds as $permId) {
                DB::table('enterprise_role_permissions')->insertOrIgnore([
                    'role_id'       => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = ['enterprise.roles.add', 'enterprise.roles.edit', 'enterprise.roles.remove', 'enterprise.roles.assign'];

        $ids = DB::table('enterprise_permissions')->whereIn('name', $names)->pluck('id');
        DB::table('enterprise_role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('enterprise_permissions')->whereIn('id', $ids)->delete();

        DB::table('enterprise_permissions')->insertOrIgnore(['name' => 'enterprise.roles.manage']);

        $old        = DB::table('enterprise_permissions')->where('name', 'enterprise.roles.manage')->first();
        $ownerRole  = DB::table('enterprise_roles')->whereNull('enterprise_id')->where('name', 'owner')->first();

        if ($old && $ownerRole) {
            DB::table('enterprise_role_permissions')->insertOrIgnore([
                'role_id'       => $ownerRole->id,
                'permission_id' => $old->id,
            ]);
        }
    }
};
