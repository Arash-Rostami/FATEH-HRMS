<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'feed_id' => Feed::inRandomOrder()->value('id') ?? Feed::factory(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraph(),
        ];
    }
}
