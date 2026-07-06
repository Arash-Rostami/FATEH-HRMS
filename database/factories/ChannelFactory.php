<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(ChannelType::cases()),
            'owner_id' => User::factory(),
        ];
    }

    public function ofType(ChannelType $type): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => $type,
        ]);
    }

    public function ownedBy(User|int $user): static
    {
        return $this->state(fn(array $attributes) => [
            'owner_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }
}
