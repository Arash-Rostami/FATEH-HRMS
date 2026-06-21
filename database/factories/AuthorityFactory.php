<?php

namespace Database\Factories;

use App\Models\Authority;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AuthorityFactory extends Factory
{
    protected $model = Authority::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code,
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'sub_duty' => fake()->boolean(),
            'details' => [],
        ];
    }
}
