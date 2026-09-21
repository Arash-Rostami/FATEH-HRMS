<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'owner_id' => User::factory(),
            'project_id' => Project::factory(),
            'template_id' => null,
            'steps' => fn (array $attributes) => [
                [
                    'label' => fake()->word(),
                    'assignee_user_ids' => [$attributes['owner_id']],
                    'type' => 'action',
                    'reject_to_step' => null,
                    'populate_task' => false,
                    'task_title' => null,
                    'task_description' => null,
                    'deadline_days' => null,
                ],
            ],
            'current_step' => null,
            'status' => Workflow::STATUS_DRAFT,
            'step_log' => [],
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function asTemplate(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Workflow::STATUS_ACTIVE,
            'current_step' => 0,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Workflow::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Workflow::STATUS_CANCELLED,
        ]);
    }
}
