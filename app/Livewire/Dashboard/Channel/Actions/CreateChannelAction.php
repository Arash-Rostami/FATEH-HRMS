<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Livewire\Dashboard\Channel\Forms\CreateChannelForm;
use App\Models\Channel;
use App\Models\ChannelMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateChannelAction
{
    public function execute(CreateChannelForm $form): Channel
    {
        $this->resolveSlug($form);

        $form->validate();

        $ownerId = auth()->id();

        return DB::transaction(function () use ($form, $ownerId) {
            $channel = Channel::create([
                'name'        => trim($form->name),
                'slug'        => $form->slug,
                'description' => $form->description,
                'type'        => $form->type,
                'owner_id'    => $ownerId,
            ]);

            ChannelMember::insertOrIgnore([
                'channel_id'           => $channel->id,
                'user_id'              => $ownerId,
                'joined_at'            => now(),
                'last_read_message_id' => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            return $channel;
        });
    }

    private function resolveSlug(CreateChannelForm $form): void
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim($form->name)));
        $base = preg_replace('/-{2,}/u', '-', trim($base, '-'));
        $form->slug = ($base !== '' ? $base : 'channel') . '-' . Str::random(6);
    }
}