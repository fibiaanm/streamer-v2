<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    protected $model = ListItem::class;

    public function definition(): array
    {
        return [
            'list_id'           => AssistantList::factory(),
            'added_by_user_id'  => User::factory(),
            'content'           => fake()->words(3, true),
            'status'            => 'pending',
            'position'          => 0,
        ];
    }

    public function done(): static
    {
        return $this->state(['status' => 'done']);
    }
}
