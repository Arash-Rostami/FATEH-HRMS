<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'feed_id' => Feed::inRandomOrder()->value('id') ?? Feed::factory(),
            'emoji' => fake()->word(),
        ];
    }
}
