<?php
namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use Illuminate\Support\Str;

class SearchMessagesAction
{
    public const MIN_LEN = 3;
    public const MAX_LEN = 64;
    public const LIMIT = 20;

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

        return Message::query()
            ->select(['id', 'body', 'sender_id', 'created_at'])
            ->where(fn($qb) => $qb
                ->where('sender_id', $authId)->where('recipient_id', $userId)
                ->orWhere(fn($q2) => $q2->where('sender_id', $userId)->where('recipient_id', $authId))
            )
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
            ->with('sender:id,name')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => Str::limit(strip_tags($m->body), 80),
                'time' => toJalaliRelative($m->created_at, short: true),
                'sender_name' => $m->sender?->name ?? 'ناشناس',
                'is_mine' => $m->sender_id === $authId,
            ])
            ->all();
    }
}