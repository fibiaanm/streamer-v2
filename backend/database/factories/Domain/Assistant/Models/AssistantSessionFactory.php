<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantSession>
 */
class AssistantSessionFactory extends Factory
{
    protected $model = AssistantSession::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'started_at'      => now(),
            'last_message_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['last_message_at' => now()->subHours(25)]);
    }
}
