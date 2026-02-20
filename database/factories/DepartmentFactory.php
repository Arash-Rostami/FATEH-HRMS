<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $departments = [
            'IT' => ['Information Technology', 'Technical support and infrastructure'],
            'HR' => ['Human Resources', 'Employee management and recruitment'],
            'FIN' => ['Finance', 'Financial operations and accounting'],
            'MKT' => ['Marketing', 'Marketing and communications'],
            'OPS' => ['Operations', 'Day-to-day business operations'],
            'SALES' => ['Sales', 'Sales and customer relations'],
            'LEGAL' => ['Legal', 'Legal affairs and compliance'],
            'ADMIN' => ['Administration', 'Administrative services'],
        ];

        $code = fake()->randomElement(array_keys($departments));

        return [
            'code' => $code,
            'name' => $departments[$code][0],
            'description' => $departments[$code][1],
        ];
    }

    public function code(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }

    public function name(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
