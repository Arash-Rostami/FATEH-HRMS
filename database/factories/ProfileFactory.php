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
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
                        'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'personnel_id' => fake()->unique()->numerify('PC-####'),
            'gender' => fake()->randomElement(['female', 'male']),
            'employment_type' => fake()->randomElement(['fulltime', 'parttime', 'contract']),
            'marital_status' => fake()->randomElement(['married', 'single']),
            'employment_status' => fake()->randomElement(['probational', 'working', 'terminated']),
            'degree' => fake()->randomElement(['undergraduate', 'graduate', 'postgraduate']),
            'position' => fake()->jobTitle(),
            'landline' => fake()->phoneNumber(),
            'cellphone' => fake()->phoneNumber(),
            'image' => fake()->imageUrl(), // specific request simple string image
            'about_me' => fake()->paragraph(),
            'birthdate' => fake()->date(),
        ];
    }
}
