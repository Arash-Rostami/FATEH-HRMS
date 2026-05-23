<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'personnel_id' => $this->faker->unique()->numerify('FATEH-#####'),
            'gender' => $this->faker->randomElement(['female', 'male']),
            'employment_type' => $this->faker->randomElement(['fulltime', 'parttime', 'contract']),
            'marital_status' => $this->faker->randomElement(['married', 'single']),
            'number_of_children' => $this->faker->numberBetween(0, 4),
            'employment_status' => $this->faker->randomElement(['probational', 'working', 'terminated']),
            'id_card_number' => $this->faker->unique()->numerify('##########'),
            'id_booklet_number' => $this->faker->unique()->numerify('######'),
            'degree' => $this->faker->randomElement(['undergraduate', 'graduate', 'postgraduate']),
            'field' => $this->faker->randomElement(['Computer Science', 'Software Engineering', 'Information Technology', 'Business Administration']),
            'birthdate' => $this->faker->date('Y-m-d', '-22 years'),
            'landline' => $this->faker->phoneNumber(),
            'cellphone' => $this->faker->phoneNumber(),
            'license_plate' => $this->faker->bothify('##-???-##'),
            'zip_code' => $this->faker->postcode(),
            'address' => $this->faker->address(),
            'accessibility' => null,
            'department_id' => $this->faker->bothify('DEP-###'),
            'position' => $this->faker->jobTitle(),
            'insurance' => $this->faker->numerify('INS-########'),
            'emergency_phone' => $this->faker->phoneNumber(),
            'emergency_relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling']),
            'start_date' => $this->faker->date(),
            'end_date' => null,
            'work_experience' => $this->faker->paragraph(),
            'interests' => $this->faker->words(3, true),
            'image' => null,
            'attachments' => [],
            'favorite_colors' => ['#6366f1', '#14b8a6'],
            'about_me' => [],
        ];
    }
}
