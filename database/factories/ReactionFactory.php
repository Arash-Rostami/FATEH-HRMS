<?php

namespace Database\Factories;

use App\Models\Reaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'feed_id' => \App\Models\Feed::inRandomOrder()->value('id') ?? \App\Models\Feed::factory(),
            'emoji' => fake()->word(),
        ];
    }
}
