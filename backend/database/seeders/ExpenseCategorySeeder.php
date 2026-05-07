<?php

namespace Database\Seeders;

use App\Domain\Assistant\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        ExpenseCategory::firstOrCreate(
            ['user_id' => null, 'enterprise_id' => null, 'name' => 'other'],
            ['emoji' => '📦'],
        );
    }
}
