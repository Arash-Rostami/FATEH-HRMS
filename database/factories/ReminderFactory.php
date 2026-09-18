<?php

namespace Database\Factories;

use App\Enums\ReminderRecurrence;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'remindable_type' => null,
            'remindable_id' => null,
            'title' => fake()->sentence(4),
            'notes' => fake()->paragraph(),
            'due_at' => fake()->dateTimeBetween('now', '+1 month'),
            'recurs' => ReminderRecurrence::None,
            'snoozed_until' => null,
            'completed_at' => null,
            'notified_at' => null,
            'channels' => ['inapp:edge,badge,nudge'],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDays(fake()->numberBetween(1, 10))->startOfDay()->subSecond(),
        ]);
    }

    public function snoozed(): static
    {
        return $this->state(fn (array $attributes) => [
            'snoozed_until' => now()->addHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now(),
        ]);
    }

    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->startOfWeek(Carbon::SATURDAY)->addDays(fake()->numberBetween(0, 6)),
        ]);
    }

    public function recurring(ReminderRecurrence $recurrence): static
    {
        return $this->state(fn (array $attributes) => [
            'recurs' => $recurrence,
        ]);
    }
}
