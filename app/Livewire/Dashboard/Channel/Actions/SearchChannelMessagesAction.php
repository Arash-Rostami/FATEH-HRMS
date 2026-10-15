<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchChannelMessagesAction
{
    public const MIN_LEN = 3;
    public const MAX_LEN = 64;
    public const LIMIT = 20;
    public const BOOKMARK_LIMIT = 100;
    public const DAY_LIMIT = 400;
    public const FILES_LIMIT = 300;

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
            ->map(fn(ChannelMessage $m) => $this->row($m, $authId))
            ->all();
    }

    public function bookmarks(int $channelId, array $ids, int $authId): array
    {
        $ids = array_slice(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)), 0, self::BOOKMARK_LIMIT);
        if (!$ids || $channelId <= 0 || $authId <= 0) {
            return [];
        }

        if (!Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', $authId))
            ->exists()) {
            return [];
        }

        $names = User::getCachedAllOptions();

        return ChannelMessage::query()
            ->select(['id', 'body', 'sender_id', 'created_at'])
            ->where('channel_id', $channelId)
            ->whereIn('id', $ids)
            ->latest('id')
            ->get()
            ->map(fn(ChannelMessage $m) => $this->row($m, $authId, $names[$m->sender_id] ?? null))
            ->all();
    }

    public function days(int $channelId, int $authId): array
    {
        if ($channelId <= 0 || $authId <= 0) {
            return [];
        }

        if (!Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', $authId))
            ->exists()) {
            return [];
        }

        return Cache::remember(
            "ch:days:{$channelId}:" . PollDigestService::version("ch:tv:{$channelId}"),
            now()->addMinutes(10),
            fn() => ChannelMessage::query()
                ->where('channel_id', $channelId)
                ->selectRaw('DATE(created_at) as day, MIN(id) as id')
                ->groupBy('day')
                ->orderByDesc('day')
                ->limit(self::DAY_LIMIT)
                ->get()
                ->map(fn($r) => ['day' => $r->day, 'id' => (int) $r->id])
                ->all()
        );
    }

    private function row(ChannelMessage $m, int $authId, ?string $senderName = null): array
    {
        return [
            'id' => $m->id,
            'body' => Str::limit(strip_tags($m->body), 80),
            'time' => toJalaliRelative($m->created_at, short: true),
            'sender_name' => $senderName ?? $m->sender?->name ?? 'ناشناس',
            'is_mine' => $m->sender_id === $authId,
        ];
    }

    public function files(int $channelId, int $authId): array
    {
        if ($channelId <= 0 || $authId <= 0) {
            return [];
        }

        if (!Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', $authId))
            ->exists()) {
            return [];
        }

        $names = User::getCachedAllOptions();

        return Cache::remember(
            "ch:files:{$channelId}:" . PollDigestService::version("ch:tv:{$channelId}"),
            now()->addMinutes(10),
            fn() => ChannelMessage::query()
                ->select(['id', 'sender_id', 'attachments', 'created_at'])
                ->where('channel_id', $channelId)
                ->whereNotNull('attachments')
                ->latest('id')
                ->limit(self::FILES_LIMIT)
                ->get()
                ->flatMap(fn(ChannelMessage $m) => $this->fileRows($m, $names[$m->sender_id] ?? ''))
                ->values()
                ->all()
        );
    }

    private function fileRows(ChannelMessage $m, string $senderName): array
    {
        return collect($m->attachments)->map(fn(array $att, int $index) => [
            'key' => $m->id . ':' . $index,
            'msg_id' => $m->id,
            'index' => $index,
            'kind' => match (true) {
                str_starts_with($att['mime'] ?? '', 'video/') => 'video',
                str_starts_with($att['mime'] ?? '', 'image/') => 'image',
                default => 'doc',
            },
            'url' => HasPublicAssetUrl::resolvePublicAssetUrl($att['path'] ?? null),
            'name' => $att['name'] ?? '',
            'size_label' => number_format(($att['size'] ?? 0) / 1024, 1) . ' KB',
            'time' => toJalaliRelative($m->created_at, short: true),
            'sender_name' => $senderName,
        ])->all();
    }
}