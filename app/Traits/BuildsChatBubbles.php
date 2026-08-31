<?php

namespace App\Traits;

use App\Models\Concerns\HasPublicAssetUrl;
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

    public function linkify(string $text): string
    {
        return preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 opacity-90 hover:opacity-100 transition-opacity">$1</a>',
            $text
        );
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
