<?php

namespace Database\Factories;

use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadFactory extends Factory
{
    protected $model = Read::class;

    public function definition(): array
    {
        $isRead = $this->faker->boolean(70);

        return [
            'document_id' => DMS::factory(),
            'user_id' => User::factory(),
            'read' => true,
            'read_count' => $isRead ? $this->faker->numberBetween(1, 30) : 0,
            'combined_read_count' => fn(array $attributes) => ($attributes['read_count'] ?? 0) + $this->faker->numberBetween(0, 10),
        ];
    }


    public function read(int $times = 1): static
    {
        return $this->state(fn(array $attributes) => [
            'read' => true,
            'read_count' => $times,
        ]);
    }


    public function unread(): static
    {
        return $this->state(fn(array $attributes) => [
            'read' => true,
            'read_count' => 0,
        ]);
    }
}
