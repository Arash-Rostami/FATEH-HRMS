<?php

namespace Database\Factories;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Models\ReleaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReleaseRequestFactory extends Factory
{
    protected $model = ReleaseRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(ReleaseRequestType::cases())->value,
            'title' => $this->faker->realText(60),
            'body' => $this->faker->realText(500),
            'status' => $this->faker->randomElement(ReleaseRequestStatus::cases())->value,
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => ReleaseRequestStatus::InReview->value,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => ReleaseRequestStatus::Open->value,
        ]);
    }
}
