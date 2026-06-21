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
            'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'category' => fake()->word(),
            'question' => fake()->paragraph(),
            'answer' => fake()->paragraph(),
        ];
    }
}
