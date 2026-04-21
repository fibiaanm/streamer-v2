<?php

use Database\Seeders\EnterprisePermissionSeeder;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Database\Seeders\EnterpriseRoleSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\WorkspacePermissionSeeder;
use Database\Seeders\WorkspaceRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed([
            EnterprisePermissionSeeder::class,
            EnterpriseRoleSeeder::class,
            EnterpriseRolePermissionSeeder::class,
            WorkspacePermissionSeeder::class,
            WorkspaceRoleSeeder::class,
            PlanSeeder::class,
        ]);
    })
    ->in('Feature', 'E2E');

pest()->extend(TestCase::class)->in('Unit');
