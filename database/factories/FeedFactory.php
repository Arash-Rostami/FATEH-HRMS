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
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'category' => fake()->word(),
            'content' => fake()->word(),
            'media_paths' => [],
            'poll_options' => [],
        ];
    }
}
