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
        $departmentCodes = Department::inRandomOrder()->limit(3)->pluck('code')->toArray();
        $isMulti = fake()->boolean(30);

        return [
            'path' => [fake()->imageUrl()],
            'title' => fake()->sentence(),
            'department_id' => !empty($departmentCodes) ? $departmentCodes[0] : null,
            'departments' => $isMulti && count($departmentCodes) > 1 ? array_slice($departmentCodes, 1) : null,
            'description' => fake()->paragraph(),
            'event_date' => fake()->date(),
        ];
    }
}
