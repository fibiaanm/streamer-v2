<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantMessage>
 */
class AssistantMessageFactory extends Factory
{
    protected $model = AssistantMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id'  => Conversation::factory(),
            'session_id'       => AssistantSession::factory(),
            'role'             => 'user',
            'channel'          => 'web',
            'content'          => fake()->sentence(),
            'memory_processed' => false,
            'created_at'       => now(),
        ];
    }

    public function assistant(): static
    {
        return $this->state(['role' => 'assistant']);
    }

    public function toolCall(): static
    {
        return $this->state(['role' => 'tool_call']);
    }

    public function toolResult(): static
    {
        return $this->state(['role' => 'tool_result']);
    }
}
