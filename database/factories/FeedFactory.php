<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedFactory extends Factory
{
    protected  = Feed::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(['general', 'announcement', 'news']),
            'content' => fake()->paragraph(),
            'media_paths' => [],
            'poll_options' => [],
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function withMedia(): static
    {
        return ->state(fn (array ) => [
            'media_paths' => [fake()->imageUrl(), fake()->imageUrl()],
        ]);
    }
}
