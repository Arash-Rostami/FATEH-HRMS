<?php

namespace Database\Factories;

use App\Models\Reaction;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    protected  = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feed_id' => Feed::factory(),
            'emoji' => fake()->randomElement(['👍', '❤️', '😂', '😮', '😢', '😡']),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
