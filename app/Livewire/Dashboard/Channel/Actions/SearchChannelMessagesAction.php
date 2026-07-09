<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
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
        if ($channelId <= 0 || $authId <= 0 || mb_strlen($q) < self::MIN_LEN) {
            return [];
        }
        $q = mb_substr($q, 0, self::MAX_LEN);

        $words = array_filter(explode(' ', preg_replace('/[+\->()~*"@]+/u', ' ', $q)));
        if (!$words) {
            return [];
        }

        if (!Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', $authId))
            ->exists()) {
            return [];
        }

        $booleanQuery = implode(' ', array_map(fn($w) => "+{$w}*", $words));

        return ChannelMessage::query()
            ->select(['id', 'body', 'sender_id', 'created_at'])
            ->where('channel_id', $channelId)
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->with('sender:id,name')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn(ChannelMessage $m) => [
                'id' => $m->id,
                'body' => Str::limit(strip_tags($m->body), 80),
                'time' => toJalaliRelative($m->created_at, short: true),
                'sender_name' => $m->sender?->name ?? 'ناشناس',
                'is_mine' => $m->sender_id === $authId,
            ])
            ->all();
    }
}