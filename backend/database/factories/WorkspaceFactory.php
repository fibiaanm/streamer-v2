<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'   => fake()->words(2, true),
            'status' => 'active',
            'path'   => '',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace) {
            if (!$workspace->parent_workspace_id) {
                $workspace->path = (string) $workspace->id;
            } else {
                $parent          = Workspace::find($workspace->parent_workspace_id);
                $workspace->path = $parent->path . '.' . $workspace->id;
            }
            $workspace->save();
        });
    }

    public function orphaned(): static
    {
        return $this->state(['status' => 'orphaned']);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }
}
