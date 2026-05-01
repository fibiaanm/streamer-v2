<?php

namespace Database\Factories\Domain\Assistant\Models;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReminder>
 */
class EventReminderFactory extends Factory
{
    protected $model = EventReminder::class;

    public function definition(): array
    {
        return [
            'event_id' => AssistantEvent::factory(),
            'fire_at'  => now()->addHours(fake()->numberBetween(1, 72)),
            'message'  => fake()->sentence(),
            'status'   => 'pending',
        ];
    }

    public function fired(): static
    {
        return $this->state([
            'status'   => 'fired',
            'fired_at' => now()->subMinutes(5),
        ]);
    }

    public function past(): static
    {
        return $this->state(['fire_at' => now()->subMinutes(5)]);
    }
}
