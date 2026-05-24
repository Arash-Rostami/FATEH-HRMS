<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'user_id' => fake()->numberBetween(1, 50),
        ];
    }
}
