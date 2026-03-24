<?php

namespace Database\Factories;


use App\Models\Department;
use App\Models\DMS;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class DMSFactory extends Factory
{
    protected $model = DMS::class;

    public function definition()
    {
        $departments = Department::all()->pluck('code')->toArray();
        $users = User::all()->pluck('id')->toArray();

        return [
            'file' => $this->faker->word(),
            'code' => $this->faker->unique()->uuid(),
            'version' => $this->faker->randomNumber(2),
            'title' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['live', 'under_review', 'obsolete']),
            'owners' => $this->getRandomElements($departments),
            'users' => $this->getRandomElements($users),
            'revision' => $this->faker->randomNumber(1),
            'combined_read_count' => $this->faker->randomNumber(3),
            'extra' => [
                'description' => $this->faker->paragraph,
            ],
        ];
    }
    private function getRandomElements($array)
    {
        return $this->faker->randomElements($array, rand(1, count($array)));
    }
}
