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
            'department_id' => \App\Models\Department::inRandomOrder()->value('id') ?? \App\Models\Department::factory(),
            'path' => json_encode([fake()->imageUrl()]),
            'title' => fake()->sentence(),
        ];
    }
}
