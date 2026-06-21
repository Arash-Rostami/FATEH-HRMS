<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'path' => [fake()->imageUrl()],
            'title' => fake()->sentence(),
            'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'description' => fake()->paragraph(),
            'event_date' => fake()->date(),
        ];
    }
}
