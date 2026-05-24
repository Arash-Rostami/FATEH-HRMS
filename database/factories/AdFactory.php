<?php

namespace Database\Factories;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'position' => fake()->jobTitle(),
            'certificate' => fake()->word(),
            'skill' => fake()->words(3, true),
            'experience' => fake()->numberBetween(1, 10) . ' years',
            'gender' => fake()->randomElement(['Male', 'Female', 'Any']),
            'link' => fake()->url(),
            'active' => fake()->boolean(),
        ];
    }
}
