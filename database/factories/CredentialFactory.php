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
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'app_name' => fake()->name(),
            'username' => fake()->name(),
            'password' => fake()->password(),
            'link' => fake()->url(),
            'note' => fake()->paragraph(),
        ];
    }
}
