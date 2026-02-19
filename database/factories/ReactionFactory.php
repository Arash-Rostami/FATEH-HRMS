<?php

namespace Database\Factories;

use App\Models\Reaction;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feed_id' => Feed::factory(),
            'emoji' => $this->faker->randomElement(['👍', '❤️', '😂', '😮', '😢', '😡']),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
