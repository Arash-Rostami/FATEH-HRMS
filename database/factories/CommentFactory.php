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
            'feed_id' => \App\Models\Feed::inRandomOrder()->value('id') ?? \App\Models\Feed::factory(),
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraph(),
        ];
    }
}
