<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::inRandomOrder()->value('id') ?? User::factory(),
            'feed_id'      => Feed::inRandomOrder()->value('id') ?? Feed::factory(),
            'option_index' => fake()->numberBetween(0, 3),
        ];
    }
}