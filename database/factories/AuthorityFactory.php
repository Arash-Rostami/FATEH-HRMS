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
            'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'sub_duty' => fake()->boolean(),
            'details' => [],
        ];
    }
}
