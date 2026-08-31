<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskDetailFactory extends Factory
{
    protected $model = TaskDetail::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'department_id' => null,
            'unit' => $this->faker->word(),
            'section' => $this->faker->word(),
            'project' => $this->faker->words(2, true),
            'scheme' => $this->faker->word(),
            'action_source_domain' => $this->faker->domainName(),
            'action_source' => $this->faker->word(),
            'collaborators' => [],
            'responsible_user_id' => User::factory(),
            'state' => $this->faker->word(),
            'attachments' => [],
            'checklist' => [],
        ];
    }
}
