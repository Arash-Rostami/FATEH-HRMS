<?php

namespace App\Traits\Livewire;

use App\Livewire\Dashboard\Messaging\Actions\ForwardMessageAction;
use App\Models\Concerns\HasPublicAssetUrl;
use App\Models\User;
use Illuminate\Support\Str;

trait BuildsChatBubbles
{
    use HasPublicAssetUrl;

    public function bubbleRadius(bool $isMine, bool $isFirst, bool $isLast): string
    {
        if ($isMine) {
            return match (true) {
                $isFirst && $isLast => 'rounded-2xl',
                $isFirst => 'rounded-2xl rounded-bl-md',
                $isLast => 'rounded-2xl rounded-tl-md',
                default => 'rounded-2xl rounded-l-md',
            };
        }

        return match (true) {
            $isFirst && $isLast => 'rounded-2xl',
            $isFirst => 'rounded-2xl rounded-br-md',
            $isLast => 'rounded-2xl rounded-tr-md',
            default => 'rounded-2xl rounded-r-md',
        };
    }

    public function attachments(array $attachments): array
    {
        return collect($attachments)->map(fn(array $file) => [
            ...$file,
            'url' => self::resolvePublicAssetUrl($file['path'] ?? null),
            'size_label' => number_format(($file['size'] ?? 0) / 1024, 1) . ' KB',
            'is_image' => str_starts_with($file['mime'] ?? '', 'image/'),
        ])->all();
    }

    public function typeFlags(array $msg): string
    {
        $flags = ($msg['body'] ?? '') !== '' ? 't' : '';

        foreach ($msg['attachments'] ?? [] as $file) {
            $mime = $file['mime'] ?? '';
            $flags .= match (true) {
                str_starts_with($mime, 'image/') => ' i',
                str_starts_with($mime, 'video/') => ' v',
                str_contains($mime, 'pdf') => ' p',
                default => ' f',
            };
        }

        return trim($flags);
    }

    public function linkify(string $text): string
    {
        return preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 opacity-90 hover:opacity-100 transition-opacity">$1</a>',
            $text
        );
    }

    private function reactionChips(array $reactions, int $authId, array $readersMap): array
    {
        if (!$reactions) {
            return [];
        }

        $fallback = null;

        return collect($reactions)->groupBy('emoji')->map(function ($rows, $emoji) use ($authId, $readersMap, &$fallback) {
            $users = $rows->map(function ($r) use ($readersMap, &$fallback) {
                $meta = $readersMap[(int) $r['user_id']] ?? null;

                return [
                    'name' => $meta['name']
                        ?? ($fallback ??= User::getCachedActiveOptions())->get((int) $r['user_id'], 'کاربر'),
                    'avatar' => $meta['avatar'] ?? null,
                    'inactive' => $meta['inactive'] ?? false,
                ];
            })->values();

            return [
                'emoji' => $emoji,
                'count' => $users->count(),
                'mine' => $rows->contains(fn($r) => (int) $r['user_id'] === $authId),
                'names' => $users->pluck('name')->implode('، '),
                'users' => $users->all(),
            ];
        })->values()->all();
    }

    public function pinPreview(?int $id, ?string $body, string $sender): ?array
    {
        if (!$id || $body === null) {
            return null;
        }

        $parts = ForwardMessageAction::parts($body);
        $text = $parts['forwarded'] === null ? $body : ($parts['note'] !== '' ? $parts['note'] : $parts['forwarded']);

        return [
            'id' => $id,
            'sender' => $sender,
            'body' => $text !== '' ? Str::limit($text, 80) : 'پیوست',
        ];
    }

    public function replyPreview(?array $replyTo): ?array
    {
        if (!$replyTo) {
            return null;
        }

        return [
            'id' => (int)($replyTo['id'] ?? 0) ?: null,
            'sender_name' => $replyTo['sender']['name'] ?? 'ناشناس',
            'body' => Str::limit($replyTo['body'] ?? '', 50),
        ];
    }
}
