<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $enterprise = $user->createEnterprise($user->name);
            $freePlan   = Plan::freeFor('core');
            $enterprise->createEnterpriseProduct($freePlan);
            $user->assignOwnerRole($enterprise);
        });
    }
}
