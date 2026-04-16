<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const USERS = [
        [
            'name'  => 'Free User',
            'email' => 'free@test.com',
            'plan'  => 'Free',
        ],
        [
            'name'  => 'Pro User',
            'email' => 'pro@test.com',
            'plan'  => 'Pro',
        ],
        [
            'name'  => 'Premium User',
            'email' => 'premium@test.com',
            'plan'  => 'Premium',
        ],
        [
            'name'  => 'Teams User',
            'email' => 'teams@test.com',
            'plan'  => 'Teams',
        ],
        [
            'name'  => 'Business User',
            'email' => 'business@test.com',
            'plan'  => 'Business',
        ],
    ];

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                ],
            );

            if (! $user->wasRecentlyCreated) {
                continue;
            }

            $plan       = Plan::where('name', $data['plan'])->firstOrFail();
            $type       = $plan->type === 'individual' ? 'personal' : 'enterprise';
            $enterprise = $user->createEnterprise($data['name'], $type);
            $enterprise->createSubscription($plan);
            $user->assignOwnerRole($enterprise);
        }
    }
}
