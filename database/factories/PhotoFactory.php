<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'path' => [fake()->imageUrl()],
            'title' => fake()->sentence(),
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code,
            'description' => fake()->paragraph(),
            'event_date' => fake()->date(),
        ];
    }
}
