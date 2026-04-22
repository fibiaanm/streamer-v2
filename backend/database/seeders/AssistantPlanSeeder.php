<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Seeder;

class AssistantPlanSeeder extends Seeder
{
    public function run(): void
    {
        $assistant = Product::where('slug', 'assistant')->firstOrFail();

        $plans = [
            [
                'name'                => 'Free',
                'is_free'             => true,
                'price_monthly_cents' => null,
                'price_yearly_cents'  => null,
                'limits_json'         => [
                    'assistant_enabled'  => ['type' => 'boolean', 'value' => true],
                    'friends'            => ['type' => 'permanent', 'max' => 5],
                    'events_monthly'     => ['type' => 'monthly',   'max' => 50],
                    'lists'              => ['type' => 'permanent', 'max' => 10],
                    'expenses_enabled'   => ['type' => 'boolean',   'value' => false],
                    'memory_categories'  => ['type' => 'permanent', 'max' => 3],
                ],
            ],
            [
                'name'                => 'Pro',
                'is_free'             => false,
                'price_monthly_cents' => 600,
                'price_yearly_cents'  => 5760,
                'limits_json'         => [
                    'assistant_enabled'  => ['type' => 'boolean', 'value' => true],
                    'friends'            => ['type' => 'permanent', 'max' => -1],
                    'events_monthly'     => ['type' => 'monthly',   'max' => -1],
                    'lists'              => ['type' => 'permanent', 'max' => -1],
                    'expenses_enabled'   => ['type' => 'boolean',   'value' => true],
                    'memory_categories'  => ['type' => 'permanent', 'max' => -1],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(
                ['name' => $plan['name'], 'product_id' => $assistant->id],
                $plan + ['product_id' => $assistant->id],
            );
        }
    }
}
