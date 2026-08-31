<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchChannelsAction
{
    public function execute(int $viewerId, string $search = '', string $filter = 'all'): Collection
    {
        $lastMsgSub = DB::table('channel_messages')
            ->selectRaw('channel_id, MAX(id) as last_message_id')
            ->whereNull('deleted_at')
            ->groupBy('channel_id');

        $unreadSub = DB::table('channel_messages')
            ->selectRaw('channel_members.channel_id, COUNT(channel_messages.id) AS unread_count')
            ->join('channel_members', function ($j) {
                $j->on('channel_members.channel_id', '=', 'channel_messages.channel_id')
                  ->where('channel_messages.id', '>', DB::raw('COALESCE(channel_members.last_read_message_id, 0)'));
            })
            ->whereNull('channel_messages.deleted_at')
            ->where('channel_members.user_id', $viewerId)
            ->groupBy('channel_members.channel_id');

        $membersSub = DB::table('channel_members')
            ->selectRaw('channel_id, COUNT(*) as members_count')
            ->groupBy('channel_id');

        return Channel::query()
            ->select('channels.*', 'channel_members.entered_at', 'lm.last_message_id', DB::raw('COALESCE(uc.unread_count, 0) as unread_count'), DB::raw('COALESCE(mc.members_count, 0) as members_count'))
            ->join('channel_members', 'channels.id', '=', 'channel_members.channel_id')
            ->leftJoinSub($lastMsgSub, 'lm', 'channels.id', '=', 'lm.channel_id')
            ->leftJoinSub($unreadSub, 'uc', 'channels.id', '=', 'uc.channel_id')
            ->leftJoinSub($membersSub, 'mc', 'channels.id', '=', 'mc.channel_id')
            ->where('channel_members.user_id', $viewerId)
            ->when(filled($search), fn($q) => $q->where(fn($g) => $g->where('channels.name', 'LIKE', "%{$search}%")->orWhere('channels.slug', 'LIKE', "%{$search}%")))
            ->when($filter === 'unread', fn($q) => $q->where('uc.unread_count', '>', 0))
            ->orderByRaw('lm.last_message_id IS NULL')
            ->orderByDesc('lm.last_message_id')
            ->orderBy('channels.name')
            ->get();
    }
}