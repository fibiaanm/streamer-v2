<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EnterprisePermissionSeeder::class,
            EnterpriseRoleSeeder::class,
            EnterpriseRolePermissionSeeder::class,
            WorkspacePermissionSeeder::class,
            WorkspaceRoleSeeder::class,
            ProductSeeder::class,
            PlanSeeder::class,
            AssistantPlanSeeder::class,
            UserSeeder::class,
            EnterpriseTeamSeeder::class,
            WorkspaceSeeder::class,
            TypeCatalogSeeder::class,
            AssistantUserSeeder::class,
        ]);
    }
}
