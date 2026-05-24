<?php

namespace Database\Factories;

use App\Models\FAQ;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class FAQFactory extends Factory
{
    protected $model = FAQ::class;

    public function definition(): array
    {
        return [
            'department_id' => fake()->word(),
            'user_id' => fake()->numberBetween(1, 50),
            'category' => fake()->word(),
            'question' => fake()->paragraph(),
            'answer' => fake()->paragraph(),
        ];
    }
}
