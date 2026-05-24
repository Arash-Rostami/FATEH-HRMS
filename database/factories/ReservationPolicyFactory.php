<?php

namespace Database\Factories;

use App\Models\ReservationPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReservationPolicyFactory extends Factory
{
    protected $model = ReservationPolicy::class;

    public function definition(): array
    {
        return [
            'resource_type' => fake()->word(),
            'key' => fake()->word(),
            'value' => [],
        ];
    }
}
