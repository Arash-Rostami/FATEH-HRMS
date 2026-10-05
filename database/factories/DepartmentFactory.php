<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->name(),
            'description' => fake()->word(),
        ];
    }
}
