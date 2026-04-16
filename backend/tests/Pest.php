<?php

use Database\Seeders\EnterprisePermissionSeeder;
use Database\Seeders\EnterpriseRolePermissionSeeder;
use Database\Seeders\EnterpriseRoleSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\WorkspacePermissionSeeder;
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
            PlanSeeder::class,
        ]);
    })
    ->in('Feature', 'E2E');

pest()->extend(TestCase::class)->in('Unit');
