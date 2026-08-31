<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'is_super_admin' => fake()->boolean(),
            'abilities' => [],
            'excluded_modules' => [],
        ];
    }
}
