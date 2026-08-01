<?php

namespace Database\Factories;

use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'repliable_type' => Ticket::class,
            'repliable_id' => Ticket::factory(),
            'body' => fake()->sentence(),
            'files' => [],
        ];
    }
}
