<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        $isInternal = $this->faker->boolean(30);
        $hasInternalUrl = $this->faker->boolean(20);

        return [
            'url' => $this->faker->url(),
            'url_title' => $this->faker->sentence(3),
            'url_description' => $this->faker->paragraph(),
            'internal_url' => $hasInternalUrl ? $this->faker->slug() : null,
            'image' => $this->faker->optional(70)->imageUrl(640, 480, 'abstract'),
            'image_description' => $this->faker->optional(50)->sentence(),
            'icon' => $this->faker->optional(60)->randomElement(['home', 'link', 'external-link', 'arrow-right', 'globe']),
            'icon_description' => $this->faker->optional(40)->words(2, true),
            'link' => $isInternal ? 'internal' : 'external',
            'sequence' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function doubleLink(): static
    {
        return $this->state(fn(array $attributes) => [
            'internal_url' => $this->faker->slug(),
        ]);
    }

    public function external(): static
    {
        return $this->state(fn(array $attributes) => [
            'link' => 'external',
            'internal_url' => null,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn(array $attributes) => [
            'link' => 'internal',
        ]);
    }
}
