<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'feed_id' => fake()->numberBetween(1, 50),
            'user_id' => fake()->numberBetween(1, 50),
            'parent_id' => null,
            'body' => fake()->paragraph(),
        ];
    }
}
