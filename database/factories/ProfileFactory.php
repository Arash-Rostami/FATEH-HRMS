<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'manager_id' => null,
            'department_id' => fake()->numberBetween(1, 10),
            'personnel_code' => fake()->unique()->numerify('PC-####'),
            'position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'cellphone' => fake()->phoneNumber(),
            'image' => fake()->imageUrl(), // specific request simple string image
            'bio' => fake()->paragraph(),
            'birthday' => fake()->date(),
        ];
    }
}
