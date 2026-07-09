<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Enums\ChannelType;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

class FetchJoinableChannelsAction
{
    public function execute(int $viewerId, string $search = ''): Collection
    {
        return Channel::withoutTrashed()
            ->with('owner:id,name')
            ->where('type', ChannelType::Open->value)
            ->whereNotIn('id', function ($q) use ($viewerId) {
                $q->select('channel_id')
                    ->from('channel_members')
                    ->where('user_id', $viewerId);
            })
            ->when(filled($search), fn($q) => $q->where(fn($g) => $g->where('name', 'LIKE', "%{$search}%")->orWhere('slug', 'LIKE', "%{$search}%")))
            ->orderBy('name')
            ->get();
    }
}