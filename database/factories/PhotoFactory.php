<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'url' => fake()->imageUrl(), // string image
            'caption' => fake()->sentence(),
        ];
    }
}
