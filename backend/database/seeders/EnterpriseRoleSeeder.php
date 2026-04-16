<?php

namespace Database\Seeders;

use App\Models\EnterpriseRole;
use Illuminate\Database\Seeder;

class EnterpriseRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['owner', 'admin', 'member', 'billing'];

        foreach ($roles as $name) {
            EnterpriseRole::firstOrCreate(
                ['name' => $name, 'enterprise_id' => null],
                ['is_default' => $name === 'member'],
            );
        }
    }
}
