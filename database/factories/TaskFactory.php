<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in-progress', 'done']),
            'deadline' => now()->addDays(fake()->numberBetween(1, 10)),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'assigned_to' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    /**
     * A task the creator still owns (no assignee) — visible on the "my-tasks" tab.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn() => [
            'user_id' => $user->id,
            'assigned_to' => null,
        ]);
    }

    /**
     * A task delegated by $delegator to $assignee — visible on the "assigned-tasks" tab for the delegator.
     */
    public function delegatedBy(User $delegator, User $assignee): static
    {
        return $this->state(fn() => [
            'user_id' => $delegator->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    /**
     * Pin the task status. Accepts the TaskStatus enum value or raw string.
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn() => ['status' => $status]);
    }

    /**
     * Stamp updated_at onto the task, used to exercise the 45-day Done recency window.
     */
    public function updatedAt(string $relative): static
    {
        return $this->state([
            'updated_at' => now()->modify($relative),
            'created_at' => now()->modify($relative),
        ]);
    }
}