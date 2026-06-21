<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FAQ;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FAQFactory extends Factory
{
    protected $model = FAQ::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code,
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'category' => fake()->word(),
            'question' => fake()->paragraph(),
            'answer' => fake()->paragraph(),
        ];
    }
}
