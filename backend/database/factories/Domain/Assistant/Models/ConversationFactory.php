<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
