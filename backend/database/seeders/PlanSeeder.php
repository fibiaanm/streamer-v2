<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                => 'Personal Free',
                'type'                => 'individual',
                'is_free'             => true,
                'price_monthly_cents' => null,
                'price_yearly_cents'  => null,
                'limits_json'         => [
                    'members'            => ['type' => 'permanent',  'max' => 1],
                    'workspaces'         => ['type' => 'permanent',  'max' => 3],
                    'workspace_depth'    => ['type' => 'permanent',  'max' => 1],
                    'storage_gb'         => ['type' => 'permanent',  'max' => 1],
                    'streams_concurrent' => ['type' => 'concurrent', 'max' => 0],
                    'stream_minutes'     => ['type' => 'monthly',    'max' => 0],
                    'rooms_concurrent'   => ['type' => 'concurrent', 'max' => 1],
                    'room_participants'  => ['type' => 'concurrent', 'max' => 4],
                    'room_guests'        => ['type' => 'concurrent', 'max' => 0],
                ],
            ],
            [
                'name'                => 'Personal Pro',
                'type'                => 'individual',
                'is_free'             => false,
                'price_monthly_cents' => 900,   // $9.00
                'price_yearly_cents'  => 8640,  // $86.40 (20% off)
                'limits_json'         => [
                    'members'            => ['type' => 'permanent',  'max' => 3],
                    'workspaces'         => ['type' => 'permanent',  'max' => 15],
                    'workspace_depth'    => ['type' => 'permanent',  'max' => 3],
                    'storage_gb'         => ['type' => 'permanent',  'max' => 20],
                    'streams_concurrent' => ['type' => 'concurrent', 'max' => 1],
                    'stream_minutes'     => ['type' => 'monthly',    'max' => 600],
                    'rooms_concurrent'   => ['type' => 'concurrent', 'max' => 3],
                    'room_participants'  => ['type' => 'concurrent', 'max' => 10],
                    'room_guests'        => ['type' => 'concurrent', 'max' => 5],
                ],
            ],
            [
                'name'                => 'Enterprise Free',
                'type'                => 'enterprise',
                'is_free'             => true,
                'price_monthly_cents' => null,
                'price_yearly_cents'  => null,
                'limits_json'         => [
                    'members'            => ['type' => 'permanent',  'max' => 5],
                    'workspaces'         => ['type' => 'permanent',  'max' => 20],
                    'workspace_depth'    => ['type' => 'permanent',  'max' => 2],
                    'storage_gb'         => ['type' => 'permanent',  'max' => 5],
                    'streams_concurrent' => ['type' => 'concurrent', 'max' => 1],
                    'stream_minutes'     => ['type' => 'monthly',    'max' => 300],
                    'rooms_concurrent'   => ['type' => 'concurrent', 'max' => 2],
                    'room_participants'  => ['type' => 'concurrent', 'max' => 10],
                    'room_guests'        => ['type' => 'concurrent', 'max' => 5],
                ],
            ],
            [
                'name'                => 'Enterprise Pro',
                'type'                => 'enterprise',
                'is_free'             => false,
                'price_monthly_cents' => 4900,  // $49.00
                'price_yearly_cents'  => 47040, // $470.40 (20% off)
                'limits_json'         => [
                    'members'            => ['type' => 'permanent',  'max' => 25],
                    'workspaces'         => ['type' => 'permanent',  'max' => 100],
                    'workspace_depth'    => ['type' => 'permanent',  'max' => 5],
                    'storage_gb'         => ['type' => 'permanent',  'max' => 50],
                    'streams_concurrent' => ['type' => 'concurrent', 'max' => 3],
                    'stream_minutes'     => ['type' => 'monthly',    'max' => 3000],
                    'rooms_concurrent'   => ['type' => 'concurrent', 'max' => 5],
                    'room_participants'  => ['type' => 'concurrent', 'max' => 50],
                    'room_guests'        => ['type' => 'concurrent', 'max' => 20],
                ],
            ],
            [
                'name'                => 'Enterprise',
                'type'                => 'enterprise',
                'is_free'             => false,
                'price_monthly_cents' => null, // precio negociado
                'price_yearly_cents'  => null,
                'limits_json'         => [
                    'members'            => ['type' => 'permanent',  'max' => -1],
                    'workspaces'         => ['type' => 'permanent',  'max' => -1],
                    'workspace_depth'    => ['type' => 'permanent',  'max' => -1],
                    'storage_gb'         => ['type' => 'permanent',  'max' => -1],
                    'streams_concurrent' => ['type' => 'concurrent', 'max' => -1],
                    'stream_minutes'     => ['type' => 'monthly',    'max' => -1],
                    'rooms_concurrent'   => ['type' => 'concurrent', 'max' => -1],
                    'room_participants'  => ['type' => 'concurrent', 'max' => -1],
                    'room_guests'        => ['type' => 'concurrent', 'max' => -1],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
