<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelMessageFactory extends Factory
{
    protected $model = ChannelMessage::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->optional(0.9)->paragraph(),
            'attachments' => [],
            'reply_to_id' => null,
            'is_edited' => false,
        ];
    }

    public function withAttachments(int $count = 2): static
    {
        return $this->state(function (array $attributes) use ($count){
            $channelId = $attributes['channel_id'] instanceof Channel
                ? $attributes['channel_id']->id
                : $attributes['channel_id'];

            $messageId = $this->faker->numberBetween(1, 99999);

            return [
                'attachments' => array_map(
                    fn(int $i) => [
                        'path' => "channel_messages/{$channelId}/{$messageId}/" . $this->faker->uuid() . '.png',
                        'name' => $this->faker->word() . '.png',
                        'mime_type' => 'image/png',
                        'size' => $this->faker->numberBetween(1024, 10485760),
                    ],
                    range(1, $count)
                ),
            ];
        });
    }

    public function replyTo(ChannelMessage $parent): static
    {
        return $this->state(fn(array $attributes) => [
            'channel_id' => $parent->channel_id,
            'reply_to_id' => $parent->id,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_edited' => true,
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => $this->faker->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }

    public function inChannel(Channel|int $channel): static
    {
        return $this->state(fn(array $attributes) => [
            'channel_id' => $channel instanceof Channel ? $channel->id : $channel,
        ]);
    }

    public function fromUser(User|int $user): static
    {
        return $this->state(fn(array $attributes) => [
            'sender_id' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
