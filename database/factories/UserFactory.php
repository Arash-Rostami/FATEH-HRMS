<?php

namespace Database\Factories;

use App\Enums\PresenceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'maximum' => fake()->numberBetween(1, 12),
            'type' => fake()->randomElement(['employee', 'manager', 'customer']),
            'role' => fake()->randomElement(['user', 'admin']),
            'status' => fake()->randomElement(['active', 'inactive']),
            'presence' => fake()->randomElement(array_column(PresenceStatus::cases(), 'value')),
            'booking' => '[{"key":"all","value":false},{"key":"car","value":false},{"key":"seat","value":true},{"key":"spot","value":true},{"key":"meeting","value":true}]',
            'last_seen' => now(),
            'extra' => ['preferences' => ['theme' => 'light']],
            'remember_token' => Str::random(10),
        ];
    }
}
