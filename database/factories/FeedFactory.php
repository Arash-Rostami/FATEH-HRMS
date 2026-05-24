<?php

namespace Database\Factories;

use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class FeedFactory extends Factory
{
    protected $model = Feed::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'category' => fake()->word(),
            'content' => fake()->word(),
            'media_paths' => fake()->word(),
            'poll_options' => fake()->word(),
        ];
    }
}
