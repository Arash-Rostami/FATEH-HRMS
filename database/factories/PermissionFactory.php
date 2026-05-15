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
            'is_super_admin' => false,
            'abilities' => ['view_dashboard', 'edit_profile'],
            'excluded_modules' => ['analytics', 'reports'],
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_super_admin' => true,
            'abilities' => null,
            'excluded_modules' => null,
        ]);
    }
}
