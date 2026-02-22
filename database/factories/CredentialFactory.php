<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'app_name' => $this->faker->company(),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'link' => $this->faker->url(),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
