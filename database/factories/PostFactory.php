<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected  = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'image' => fake()->imageUrl(),
            'pinned' => false,
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function pinned(): static
    {
        return ->state(fn (array ) => [
            'pinned' => true,
        ]);
    }
}
