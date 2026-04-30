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
                    'assistant_enabled'    => ['type' => 'boolean',   'value' => true],
                    'messages_daily'       => ['type' => 'daily',      'max' => 15],
                    'memory_total'         => ['type' => 'permanent',  'max' => 15],
                    'memory_categories'    => ['type' => 'permanent',  'max' => 2],
                    'friends'              => ['type' => 'permanent',  'max' => 5],
                    'friend_categories'    => ['type' => 'permanent',  'max' => 2],
                    'lists'                => ['type' => 'permanent',  'max' => 3],
                    'list_items_max'       => ['type' => 'permanent',  'max' => 15],
                    'events_monthly'       => ['type' => 'monthly',    'max' => 20],
                    'reminders_active'     => ['type' => 'permanent',  'max' => 3],
                    'context_messages'     => ['type' => 'permanent',  'max' => 20],
                    'storage_mb'           => ['type' => 'permanent',  'max' => 0],
                    'upload_monthly_mb'    => ['type' => 'monthly',    'max' => 0],
                    'upload_max_mb'        => ['type' => 'permanent',  'max' => 0],
                    'expenses_enabled'     => ['type' => 'boolean',    'value' => false],
                    'custom_instructions'  => ['type' => 'boolean',    'value' => false],
                    'export_enabled'       => ['type' => 'boolean',    'value' => false],
                ],
            ],
            [
                'name'                => 'Pro',
                'is_free'             => false,
                'price_monthly_cents' => 400,
                'price_yearly_cents'  => 3840,
                'limits_json'         => [
                    'assistant_enabled'    => ['type' => 'boolean',   'value' => true],
                    'messages_daily'       => ['type' => 'daily',      'max' => 60],
                    'memory_total'         => ['type' => 'permanent',  'max' => 60],
                    'memory_categories'    => ['type' => 'permanent',  'max' => 5],
                    'friends'              => ['type' => 'permanent',  'max' => 30],
                    'friend_categories'    => ['type' => 'permanent',  'max' => 5],
                    'lists'                => ['type' => 'permanent',  'max' => 15],
                    'list_items_max'       => ['type' => 'permanent',  'max' => 50],
                    'events_monthly'       => ['type' => 'monthly',    'max' => 100],
                    'reminders_active'     => ['type' => 'permanent',  'max' => 15],
                    'context_messages'     => ['type' => 'permanent',  'max' => 60],
                    'storage_mb'           => ['type' => 'permanent',  'max' => 2048],
                    'upload_monthly_mb'    => ['type' => 'monthly',    'max' => 512],
                    'upload_max_mb'        => ['type' => 'permanent',  'max' => 20],
                    'expenses_enabled'     => ['type' => 'boolean',    'value' => true],
                    'custom_instructions'  => ['type' => 'boolean',    'value' => false],
                    'export_enabled'       => ['type' => 'boolean',    'value' => true],
                ],
            ],
            [
                'name'                => 'Premium',
                'is_free'             => false,
                'price_monthly_cents' => 500,
                'price_yearly_cents'  => 4800,
                'limits_json'         => [
                    'assistant_enabled'    => ['type' => 'boolean',   'value' => true],
                    'messages_daily'       => ['type' => 'daily',      'max' => -1],
                    'memory_total'         => ['type' => 'permanent',  'max' => -1],
                    'memory_categories'    => ['type' => 'permanent',  'max' => -1],
                    'friends'              => ['type' => 'permanent',  'max' => -1],
                    'friend_categories'    => ['type' => 'permanent',  'max' => -1],
                    'lists'                => ['type' => 'permanent',  'max' => -1],
                    'list_items_max'       => ['type' => 'permanent',  'max' => -1],
                    'events_monthly'       => ['type' => 'monthly',    'max' => -1],
                    'reminders_active'     => ['type' => 'permanent',  'max' => -1],
                    'context_messages'     => ['type' => 'permanent',  'max' => 200],
                    'storage_mb'           => ['type' => 'permanent',  'max' => 20480],
                    'upload_monthly_mb'    => ['type' => 'monthly',    'max' => 4096],
                    'upload_max_mb'        => ['type' => 'permanent',  'max' => 50],
                    'expenses_enabled'     => ['type' => 'boolean',    'value' => true],
                    'custom_instructions'  => ['type' => 'boolean',    'value' => true],
                    'export_enabled'       => ['type' => 'boolean',    'value' => true],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name'], 'product_id' => $assistant->id],
                $plan + ['product_id' => $assistant->id],
            );
        }
    }
}
