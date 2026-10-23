<?php
namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchMessagesAction
{
    public const MIN_LEN = 3;
    public const MAX_LEN = 64;
    public const LIMIT = 20;
    public const BOOKMARK_LIMIT = 100;
    public const DAY_LIMIT = 400;
    public const FILES_LIMIT = 300;

    public function execute(int $userId, string $query, int $authId): array
    {
        $q = trim($query);
        if ($userId <= 0 || $authId <= 0 || mb_strlen($q) < self::MIN_LEN) {
            return [];
        }
        $q = mb_substr($q, 0, self::MAX_LEN);

        $words = array_filter(explode(' ', preg_replace('/[+\->()~*"@]+/u', ' ', $q)));
        if (!$words) {
            return [];
        }

        $booleanQuery = implode(' ', array_map(fn($w) => "+{$w}*", $words));

        $direction = fn(int $senderId, int $recipientId) => Message::query()
            ->select(['id', 'body', 'sender_id', 'created_at'])
            ->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId)
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);

        return $direction($authId, $userId)->latest('id')->limit(self::LIMIT)->get()
            ->merge($direction($userId, $authId)->latest('id')->limit(self::LIMIT)->get())
            ->sortByDesc('id')->take(self::LIMIT)->values()
            ->load('sender:id,name')
            ->map(fn (Message $m) => $this->row($m, $authId))
            ->all();
    }

    public function bookmarks(int $userId, array $ids, int $authId): array
    {
        $ids = array_slice(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)), 0, self::BOOKMARK_LIMIT);
        if (!$ids || $userId <= 0 || $authId <= 0) {
            return [];
        }

        $names = User::getCachedAllOptions();

        return Message::query()
            ->select(['id', 'body', 'sender_id', 'recipient_id', 'created_at'])
            ->whereIn('id', $ids)
            ->whereIn('sender_id', [$authId, $userId])
            ->whereIn('recipient_id', [$authId, $userId])
            ->get()
            ->filter(fn(Message $m) => ($m->sender_id === $authId && $m->recipient_id === $userId)
                || ($m->sender_id === $userId && $m->recipient_id === $authId))
            ->sortByDesc('id')
            ->map(fn(Message $m) => $this->row($m, $authId, $names[$m->sender_id] ?? null))
            ->values()
            ->all();
    }

    public function days(int $userId, int $authId): array
    {
        if ($userId <= 0 || $authId <= 0) {
            return [];
        }

        return Cache::remember(
            "dm:days:{$authId}:{$userId}:" . PollDigestService::version("dm:sv:{$authId}"),
            now()->addMinutes(10),
            function () use ($userId, $authId) {
                $direction = fn(int $senderId, int $recipientId) => Message::query()
                    ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
                    ->where('sender_id', $senderId)
                    ->where('recipient_id', $recipientId)
                    ->selectRaw('DATE(created_at) as day, MIN(id) as id')
                    ->groupBy('day')
                    ->get();

                return $direction($authId, $userId)->concat($direction($userId, $authId))
                    ->groupBy('day')
                    ->map(fn($g) => ['day' => $g->first()->day, 'id' => (int) $g->min('id')])
                    ->sortKeysDesc()
                    ->take(self::DAY_LIMIT)
                    ->values()
                    ->all();
            }
        );
    }

    private function row(Message $m, int $authId, ?string $senderName = null): array
    {
        return [
            'id' => $m->id,
            'body' => Str::limit(strip_tags($m->body), 80),
            'time' => toJalaliRelative($m->created_at, short: true),
            'sender_name' => $senderName ?? $m->sender?->name ?? 'ناشناس',
            'is_mine' => $m->sender_id === $authId,
        ];
    }

    public function files(int $userId, int $authId): array
    {
        if ($userId <= 0 || $authId <= 0) {
            return [];
        }

        $names = User::getCachedAllOptions();
        $v = implode(':', PollDigestService::versions(["dm:sv:{$authId}", "dm:sv:{$userId}"]));

        return Cache::remember(
            "dm:files:{$authId}:{$userId}:{$v}",
            now()->addMinutes(10),
            function () use ($userId, $authId, $names) {
                $direction = fn(int $senderId, int $recipientId) => Message::query()
                    ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
                    ->select(['id', 'sender_id', 'attachments', 'created_at'])
                    ->where('sender_id', $senderId)
                    ->where('recipient_id', $recipientId)
                    ->whereNotNull('attachments')
                    ->latest('id')
                    ->limit(self::FILES_LIMIT);

                return $direction($authId, $userId)->get()
                    ->merge($direction($userId, $authId)->get())
                    ->sortByDesc('id')
                    ->flatMap(fn(Message $m) => $this->fileRows($m, $names[$m->sender_id] ?? ''))
                    ->values()
                    ->all();
            }
        );
    }

    private function fileRows(Message $m, string $senderName): array
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