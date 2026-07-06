<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use Illuminate\Support\Str;

class SearchChannelMessagesAction
{
    public const MIN_LEN = 3;
    public const MAX_LEN = 64;
    public const LIMIT = 20;

    public function execute(int $channelId, string $query, int $authId): array
    {
        $q = trim($query);
        if ($channelId === 0 || $authId === 0 || mb_strlen($q) < self::MIN_LEN) {
            return [];
        }
        if (mb_strlen($q) > self::MAX_LEN) {
            $q = mb_substr($q, 0, self::MAX_LEN);
        }

        $term = preg_replace('/[+\->()~*"@]/u', '', $q);
        if ($term === '') {
            return [];
        }

        $isMember = ChannelMember::query()
            ->where('user_id', $authId)
            ->where('channel_id', $channelId)
            ->exists();

        if (! $isMember) {
            return [];
        }

        return ChannelMessage::query()
            ->where('channel_id', $channelId)
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$term . '*'])
            ->with('sender:id,name')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (ChannelMessage $m) => [
                'id' => $m->id,
                'body' => Str::limit(strip_tags($m->body), 80),
                'time' => toJalaliRelative($m->created_at, short: true),
                'sender_name' => $m->sender?->name ?? 'ناشناس',
                'is_mine' => $m->sender_id === $authId,
            ])
            ->all();
    }
}