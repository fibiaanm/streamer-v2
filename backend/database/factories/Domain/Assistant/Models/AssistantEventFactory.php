<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistantEvent>
 */
class AssistantEventFactory extends Factory
{
    protected $model = AssistantEvent::class;

    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'content'  => fake()->sentence(),
            'event_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'type'     => fake()->word(),
            'status'   => 'active',
        ];
    }

    public function master(string $rrule = 'FREQ=WEEKLY'): static
    {
        return $this->state([
            'series_id'              => null,
            'occurrence_at'          => null,
            'recurrence_rule'        => $rrule,
            'reminders_template_json' => [],
        ]);
    }

    public function occurrence(AssistantEvent $master): static
    {
        return $this->state([
            'user_id'          => $master->user_id,
            'series_id'        => $master->id,
            'occurrence_at'    => $this->faker->dateTimeBetween('now', '+14 days'),
            'recurrence_rule'  => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
