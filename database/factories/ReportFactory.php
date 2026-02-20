<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => true,
        ]);
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'cover_image' => fake()->optional()->imageUrl(800, 600, 'business'),
            'department_id' => fake()->optional()->randomElement(['IT', 'HR', 'FIN', 'MKT', 'OPS']),
            'file_path' => 'reports/' . fake()->uuid() . '.pdf',
            'active' => fake()->boolean(80),
        ];
    }

    public function doc(): static
    {
        return $this->state(fn(array $attributes) => [
            'file_path' => 'reports/' . fake()->uuid() . '.doc',
        ]);
    }

    public function docx(): static
    {
        return $this->state(fn(array $attributes) => [
            'file_path' => 'reports/' . fake()->uuid() . '.docx',
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => false,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn(array $attributes) => [
            'file_path' => 'reports/' . fake()->uuid() . '.pdf',
        ]);
    }

    public function withCover(): static
    {
        return $this->state(fn(array $attributes) => [
            'cover_image' => fake()->imageUrl(800, 600, 'business'),
        ]);
    }

    public function withDepartment(string $deptId): static
    {
        return $this->state(fn(array $attributes) => [
            'department_id' => $deptId,
        ]);
    }

    public function withoutCover(): static
    {
        return $this->state(fn(array $attributes) => [
            'cover_image' => null,
        ]);
    }
}
