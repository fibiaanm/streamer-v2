<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\Memory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoryFactory extends Factory
{
    protected $model = Memory::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'category'    => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'content'     => $this->faker->paragraph(),
        ];
    }
}
