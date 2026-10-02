<?php
namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use Illuminate\Support\Facades\DB;
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

        $direction = fn(int $senderId, int $recipientId) => Message::query()
            ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
            ->select(['id', 'body', 'sender_id', 'created_at'])
            ->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId)
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);

        return $direction($authId, $userId)->latest('id')->limit(self::LIMIT)->get()
            ->merge($direction($userId, $authId)->latest('id')->limit(self::LIMIT)->get())
            ->sortByDesc('id')->take(self::LIMIT)->values()
            ->load('sender:id,name')
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