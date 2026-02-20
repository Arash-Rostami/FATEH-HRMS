<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => $this->faker->randomElements([
                'photos/event1.jpg',
                'photos/event2.jpg',
                'photos/event3.jpg'
            ], $this->faker->numberBetween(1, 5)),
            'title' => $this->faker->sentence(3),
            'department_id' => $this->faker->randomElement(['HR', 'IT', 'Sales', 'Marketing']),
            'description' => $this->faker->paragraph(),
            'event_date' => $this->faker->date(),
        ];
    }
}
