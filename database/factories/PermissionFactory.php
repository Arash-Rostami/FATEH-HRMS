<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'is_super_admin' => fake()->boolean(),
            'abilities' => [],
            'excluded_modules' => [],
        ];
    }
}
