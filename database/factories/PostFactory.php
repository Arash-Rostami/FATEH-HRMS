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
            'user_id' => User::factory(),
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn() => ['pinned' => 1]);
    }

    public function notPinned(): static
    {
        return $this->state(fn() => ['pinned' => 0]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn() => ['user_id' => $user->id]);
    }
}