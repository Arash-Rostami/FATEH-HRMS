<?php

namespace Database\Factories;

use App\Models\Authority;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorityFactory extends Factory
{

    protected $model = Authority::class;


    public function definition(): array
    {
        return [
            'department_id' => fn () => Department::inRandomOrder()->first()?->code
                ?? Department::factory()->create()->code,

            'user_id'       => User::factory(),
            'sub_duty'      => fake()->boolean(),
            'details'       => [
                'title'          => fake()->jobTitle(),
                'scope'          => fake()->sentence(),
                'effective_from' => fake()->date(),
                'restrictions'   => fake()->randomElement(['none', 'limited', 'strict']),
            ],
        ];
    }
}
