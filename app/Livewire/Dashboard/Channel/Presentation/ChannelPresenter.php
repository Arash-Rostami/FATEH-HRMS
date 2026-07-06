<?php

namespace App\Livewire\Dashboard\Channel\Presentation;

use App\Models\Channel;
use App\Models\Traits\HasPublicAssetUrl;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ChannelPresenter
{
    use HasPublicAssetUrl;

    public function sidebar(array $channels, int $authId): array
    {
        return array_map(fn(array $c) => $this->channel($c, $authId), $channels);
    }

    public function channel(array $c, int $authId): array
    {
        $last = $c['last_message'] ?? null;
        $unread = (int)($c['unread_count'] ?? 0);

        return [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'slug' => $c['slug'],
            'slug_handle' => $this->slugHandle($c['slug'] ?? ''),
            'description' => $c['description'] ?? '',
            'type' => $c['type'] ?? 'open',
            'initials' => mb_substr($c['name'] ?? '', 0, 1),
            'unread' => $unread,
            'last_message' => $last ? [
                'body' => Str::limit($last['body'] ?? '', 30),
                'time' => toJalaliRelative($last['created_at'] ?? null, short: true),
                'datetime' => $last['created_at'] ?? null,
                'is_mine' => (int)($last['sender_id'] ?? 0) === $authId,
            ] : null,
        ];
    }

    public function totalUnread(array $channels): int
    {
        return array_sum(array_column($channels, 'unread_count'));
    }

    public function channelHeader(Channel $channel): array
    {
        return [
            'id' => (int)$channel->id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'slug_handle' => $this->slugHandle($channel->slug ?? ''),
            'description' => $channel->description ?? '',
            'type' => $channel->type->value,
            'type_label' => $channel->type->getLabel(),
            'type_icon' => $channel->type->getMaterialIcon(),
            'type_color' => $channel->type->getMaterialColor(),
            'owner_name' => $channel->owner?->name ?? '—',
            'members_count' => (int)($channel->members_count ?? 0),
        ];
    }

    public function slugHandle(string $slug): string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return '';
        }

        return preg_match('/^\p{Arabic}/u', $slug) ? $slug . '#' : '#' . $slug;
    }

    public function browseList(array $channels): array
    {
        return array_map(fn(array $c) => [
            'id' => (int)$c['id'],
            'name' => $c['name'],
            'slug' => $c['slug'] ?? '',
            'slug_handle' => $this->slugHandle($c['slug'] ?? ''),
            'description' => $c['description'] ?? '',
            'type' => $c['type'] ?? 'open',
            'owner_name' => $c['owner_name'] ?? '—',
        ], $channels);
    }

    public function messageGroup(string $date, array $messages, int $authId, int $editTimeLimit): array
    {
        $label = Carbon::parse($date)->isToday()
            ? 'امروز'
            : (Carbon::parse($date)->isYesterday()
                ? 'دیروز'
                : Carbon::parse($date)->translatedFormat('j F Y'));

        return [
            'date' => $date,
            'label' => $label,
            'messages' => $this->messages($messages, $authId, $editTimeLimit),
        ];
    }

    public function messages(array $messages, int $authId, int $editTimeLimit): array
    {
        $total = count($messages);

        return array_map(function (array $msg, int $i) use ($messages, $total, $authId, $editTimeLimit) {
            $senderId = (int)($msg['sender_id'] ?? 0);
            $isMine = $senderId === $authId;
            $prev = $i > 0 ? $messages[$i - 1] : null;
            $next = $i < $total - 1 ? $messages[$i + 1] : null;
            $isFirst = !$prev || (int)($prev['sender_id'] ?? 0) !== $senderId;
            $isLast = !$next || (int)($next['sender_id'] ?? 0) !== $senderId;
            $createdAt = Carbon::parse($msg['created_at'] ?? now());

            return [
                'id' => (int)($msg['id'] ?? 0),
                'body' => $msg['body'] ?? '',
                'body_html' => nl2br($this->linkify(e($msg['body'] ?? '')), false),
                'created_at' => $msg['created_at'] ?? null,
                'time' => $createdAt->format('H:i'),
                'datetime' => $msg['created_at'] ?? '',
                'is_mine' => $isMine,
                'is_first' => $isFirst,
                'is_last' => $isLast,
                'is_edited' => (bool)($msg['is_edited'] ?? false),
                'is_deleted' => !empty($msg['deleted_at']),
                'sender_name' => $msg['sender']['name'] ?? 'ناشناس',
                'sender_avatar' => $msg['sender']['avatar'] ?? null,
                'can_edit' => $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds(now()) <= $editTimeLimit,
                'can_delete' => $isMine && empty($msg['deleted_at']),
                'attachments' => $this->attachments($msg['attachments'] ?? []),
                'gap_class' => $isFirst ? 'mt-4' : 'mt-1',
                'bubble_radius' => $this->bubbleRadius($isMine, $isFirst, $isLast),
                'reply_to' => $this->replyPreview($msg['reply_to'] ?? null),
                'animation_delay' => $i * 0.04,
            ];
        }, array_values($messages), array_keys($messages));
    }

    private function replyPreview(?array $replyTo): ?array
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

    private function bubbleRadius(bool $isMine, bool $isFirst, bool $isLast): string
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

    private function attachments(array $attachments): array
    {
        return collect($attachments)->map(fn(array $file) => [
            ...$file,
            'url' => self::resolvePublicAssetUrl($file['path'] ?? null),
            'size_label' => number_format(($file['size'] ?? 0) / 1024, 1) . ' KB',
            'is_image' => str_starts_with($file['mime'] ?? '', 'image/'),
        ])->all();
    }

    private function linkify(string $text): string
    {
        return preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 opacity-90 hover:opacity-100 transition-opacity">$1</a>',
            $text
        );
    }
}