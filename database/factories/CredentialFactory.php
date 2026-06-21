<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'app_name' => fake()->name(),
            'username' => fake()->name(),
            'password' => fake()->password(),
            'link' => fake()->url(),
            'note' => fake()->paragraph(),
        ];
    }
}
