<?php

namespace Database\Factories;

use App\Models\Onboarding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class OnboardingFactory extends Factory
{
    protected $model = Onboarding::class;

    public function definition(): array
    {
        return [
            'welcome' => fake()->paragraph(),
            'videos' => [],
            'mission' => fake()->paragraph(),
            'vision' => fake()->paragraph(),
            'guides' => [],
            'schedule' => fake()->paragraph(),
            'extras' => [],
            'is_active' => fake()->boolean(),
            'user_id' => fake()->numberBetween(1, 50),
        ];
    }
}
