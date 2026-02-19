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
            'user_id' => User::factory(),
            'feed_id' => Feed::factory(),
            'content' => $this->faker->sentence(),
            'parent_id' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function reply(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Comment::factory(),
            ];
        });
    }
}
