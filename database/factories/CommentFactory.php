<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected  = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feed_id' => Feed::factory(),
            'content' => fake()->sentence(),
            'parent_id' => null,
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function reply(): static
    {
        return ->state(fn (array ) => [
            'parent_id' => Comment::factory(),
        ]);
    }
}
