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
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'image' => $this->faker->imageUrl(),
            'pinned' => false,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function pinned(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'pinned' => true,
            ];
        });
    }
}
