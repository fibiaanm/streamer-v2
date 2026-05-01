<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantList>
 */
class AssistantListFactory extends Factory
{
    protected $model = AssistantList::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name'    => fake()->words(3, true),
            'type'    => 'general',
        ];
    }
}
