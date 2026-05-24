<?php

namespace Database\Factories;

use App\Models\Authority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AuthorityFactory extends Factory
{
    protected $model = Authority::class;

    public function definition(): array
    {
        return [
            'department_id' => fake()->word(),
            'user_id' => fake()->numberBetween(1, 50),
            'sub_duty' => fake()->boolean(),
            'details' => [],
        ];
    }
}
