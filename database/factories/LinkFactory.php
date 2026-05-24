<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'url_title' => fake()->sentence(),
            'url_description' => fake()->paragraph(),
            'internal_url' => fake()->url(),
            'image' => fake()->imageUrl(),
            'image_description' => fake()->imageUrl(),
            'icon' => fake()->word(),
            'icon_description' => fake()->word(),
            'link' => fake()->randomElement(['internal', 'external']),
            'sequence' => fake()->numberBetween(1, 100),
            'extra' => [],
        ];
    }
}
