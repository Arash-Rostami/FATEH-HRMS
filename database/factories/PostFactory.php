<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'image' => fake()->imageUrl(),
            'pinned' => fake()->boolean(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
