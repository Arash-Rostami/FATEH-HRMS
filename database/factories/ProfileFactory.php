<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::first()?->code ?? 'HR',
            'personnel_id' => fake()->unique()->numerify('PC-######'),
            'gender' => fake()->randomElement(['female', 'male']),
            'employment_type' => fake()->randomElement(['fulltime', 'parttime', 'contract']),
            'marital_status' => fake()->randomElement(['married', 'single']),
            'employment_status' => fake()->randomElement(['probational', 'working', 'terminated']),
            'degree' => fake()->randomElement(['undergraduate', 'graduate', 'postgraduate']),
            'position' => fake()->jobTitle(),
            'landline' => '0211' . fake()->numerify('#######'),
            'cellphone' => '0912' . fake()->numerify('#######'),
            'image' => fake()->imageUrl(),
            'about_me' => ['bio' => fake()->sentence()],
            'birthdate' => fake()->date(),
        ];
    }
}
