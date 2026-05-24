<?php

namespace Database\Factories;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'app_name' => fake()->name(),
            'username' => fake()->name(),
            'password' => fake()->paragraph(),
            'link' => fake()->url(),
            'note' => fake()->paragraph(),
        ];
    }
}
