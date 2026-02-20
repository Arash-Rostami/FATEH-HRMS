<?php

namespace Database\Factories;

use App\Models\FAQ;
use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class FAQFactory extends Factory
{
    protected $model = FAQ::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(['General', 'Technical', 'Billing', 'Support', 'HR']),
            'question' => fake()->sentence(8) . '?',
            'answer' => fake()->paragraph(3),
        ];
    }

    public function forDepartment(string $deptCode): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $deptCode,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
