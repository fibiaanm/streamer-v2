<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListShare>
 */
class ListShareFactory extends Factory
{
    protected $model = ListShare::class;

    public function definition(): array
    {
        return [
            'list_id'              => AssistantList::factory(),
            'shared_with_user_id'  => User::factory(),
            'invited_by_user_id'   => User::factory(),
            'permission'           => 'write',
            'accepted_at'          => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }
}
