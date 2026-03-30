<?php

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['seat', 'spot', 'car', 'meeting']),
            'metadata' => [
                'capacity' => $this->faker->numberBetween(2, 100),
                'location' => $this->faker->company(),
                'color' => $this->faker->colorName(),
            ],
            'status' => $this->faker->randomElement(['active', 'maintenance', 'out_of_service']),
            'image' => $this->faker->imageUrl(),
        ];
    }
}
