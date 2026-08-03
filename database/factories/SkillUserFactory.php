<?php

namespace Database\Factories;

use App\Enums\SkillRequestStatus;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillUserFactory extends Factory
{
    protected $model = SkillUser::class;

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_used_at' => now()->subDays($this->faker->numberBetween(0, 30)),
            'last_used_context' => $this->faker->sentence(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SkillRequestStatus::Approved,
            'approved_at' => now(),
            'approved_by' => User::factory(),
            'rejected_reason' => null,
        ]);
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'skill_id' => Skill::factory(),
            'status' => $this->faker->randomElement(SkillRequestStatus::cases()),
            'requested_name' => $this->faker->optional()->words(2, true),
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_used_context' => $this->faker->optional()->sentence(),
            'is_private' => $this->faker->boolean(20),
            'is_mentoring' => $this->faker->boolean(15),
            'approved_at' => null,
            'approved_by' => null,
            'rejected_reason' => null,
            'endorsers' => [],
            'endorsements_count' => 0,
        ];
    }

    public function endorsed(int $count = 3): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $endorsers = User::factory()->count($count)->create()->pluck('id')->all();

            return [
                'endorsers' => $endorsers,
                'endorsements_count' => $count,
            ];
        });
    }

    public function mentoring(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_mentoring' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SkillRequestStatus::Pending,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_reason' => null,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_private' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SkillRequestStatus::Rejected,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_reason' => $this->faker->sentence(),
        ]);
    }
}
