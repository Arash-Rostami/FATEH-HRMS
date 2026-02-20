<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'personnel_id' => fake()->unique()->numerify('EMP####'),
            'image' => fake()->optional()->imageUrl(),
            'attachments' => null,
            'gender' => fake()->randomElement(['female', 'male']),
            'employment_type' => fake()->randomElement(['fulltime', 'parttime', 'contract']),
            'marital_status' => fake()->randomElement(['married', 'single']),
            'number_of_children' => fake()->optional()->numberBetween(0, 5),
            'employment_status' => fake()->randomElement(['probational', 'working', 'terminated']),
            'id_card_number' => fake()->optional()->unique()->numerify('ID########'),
            'id_booklet_number' => fake()->optional()->unique()->numerify('BK########'),
            'degree' => fake()->randomElement(['undergraduate', 'graduate', 'postgraduate']),
            'field' => fake()->optional()->jobTitle(),
            'birthdate' => fake()->optional()->date('Y-m-d', '-18 years'),
            'landline' => fake()->optional()->phoneNumber(),
            'cellphone' => fake()->optional()->phoneNumber(),
            'license_plate' => fake()->optional()->bothify('??-###-??'),
            'zip_code' => fake()->optional()->postcode(),
            'address' => fake()->optional()->address(),
            'accessibility' => fake()->optional()->sentence(),
            'department_id' => Department::factory(),
            'position' => fake()->jobTitle(),
            'insurance' => fake()->optional()->company(),
            'emergency_phone' => fake()->optional()->phoneNumber(),
            'emergency_relationship' => fake()->optional()->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'work_experience' => fake()->optional()->paragraph(),
            'interests' => fake()->optional()->paragraph(),
            'favorite_colors' => null,
        ];
    }

    public function married(): static
    {
        return $this->state(fn (array $attributes) => [
            'marital_status' => 'married',
        ]);
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'marital_status' => 'single',
        ]);
    }

    public function fulltime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'fulltime',
        ]);
    }

    public function parttime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'parttime',
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'contract',
        ]);
    }

    public function probational(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'probational',
        ]);
    }

    public function working(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'working',
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'terminated',
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forDepartment(string $deptCode): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $deptCode,
        ]);
    }
}
