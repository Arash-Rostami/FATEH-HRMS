<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedFactory extends Factory
{
    protected $model = Feed::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => $this->faker->randomElement(['general', 'announcement', 'news']),
            'content' => $this->faker->paragraph(),
            'media_paths' => [],
            'poll_options' => [],
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    // ✅ Custom state to include media
    public function withMedia(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'media_paths' => [
                    $this->faker->imageUrl(),
                    $this->faker->imageUrl(),
                ],
            ];
        });
    }
}
