<?php

namespace Database\Factories;

use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Plan;
use App\Models\Subscription;
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
            $enterprise = Enterprise::factory()->personal()->create([
                'name'     => $user->name,
                'owner_id' => $user->id,
            ]);

            // Depende de que EnterpriseRoleSeeder haya corrido
            $ownerRole = EnterpriseRole::where('name', 'owner')
                ->whereNull('enterprise_id')
                ->firstOrFail();

            EnterpriseMember::create([
                'user_id'       => $user->id,
                'enterprise_id' => $enterprise->id,
                'role_id'       => $ownerRole->id,
                'status'        => 'active',
            ]);

            $freePlan = Plan::where('name', 'Personal Free')->firstOrFail();

            Subscription::create([
                'enterprise_id' => $enterprise->id,
                'plan_id'       => $freePlan->id,
                'status'        => 'active',
                'starts_at'     => now(),
            ]);
        });
    }
}
