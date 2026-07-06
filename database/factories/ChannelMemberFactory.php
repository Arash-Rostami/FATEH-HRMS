<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelMemberFactory extends Factory
{
    protected $model = ChannelMember::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'last_read_message_id' => null,
            'joined_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function forUser(User|int $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }

    public function inChannel(Channel|int $channel): static
    {
        return $this->state(fn(array $attributes) => [
            'channel_id' => $channel instanceof Channel ? $channel->id : $channel,
        ]);
    }

    public function lastRead(ChannelMessage|int|null $message): static
    {
        return $this->state(fn(array $attributes) => [
            'last_read_message_id' => $message instanceof ChannelMessage ? $message->id : $message,
        ]);
    }
}
