<?php

namespace App\Livewire\Dashboard\Contact\Presentation;

use App\Enums\PresenceStatus;
use App\Models\Traits\HasAvatar;
use App\Models\Traits\HasPublicAssetUrl;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContactPresenter
{
    use HasAvatar, HasPublicAssetUrl;

    public function sidebar(array $contacts, int $authId): array
    {
        return array_map(fn(array $c) => $this->contact($c, $authId), $contacts);
    }

    public function contact(array $c, int $authId): array
    {
        $last  = $c['last_message'] ?? null;
        $unread = (int) ($c['unread_count'] ?? 0);

        return [
            'id'       => $c['id'],
            'name'     => $c['name'],
            'avatar'   => $this->resolveImageUrl($c['profile']['image'] ?? null),
            'position' => $c['profile']['position'] ?? 'عضو سازمان',
            'is_online' => (bool) ($c['is_online'] ?? false),
            'presence'  => ($c['presence'] ?? null) instanceof PresenceStatus ? $c['presence'] : null,
            'unread'    => $unread,
            'occasion'  => $c['occasion'] ?? null,
            'last_message' => $last ? [
                'body'       => Str::limit($last['body'], 30),
                'time'       => toJalaliRelative($last['created_at'], short: true),
                'datetime'   => $last['created_at'],
                'is_mine'    => (int) ($last['sender_id'] ?? 0) === $authId,
                'is_read'    => !empty($last['read_at']),
            ] : null,
        ];
    }

    public function totalUnread(array $contacts): int
    {
        return array_sum(array_column($contacts, 'unread_count'));
    }

    public function totalOccasions(array $contacts): int
    {
        return collect($contacts)->filter(fn(array $c) => !empty($c['occasion']))->count();
    }

    public function messageGroup(string $date, array $messages, int $authId, int $editTimeLimit): array
    {
        $label = Carbon::parse($date)->isToday()
            ? 'امروز'
            : (Carbon::parse($date)->isYesterday()
                ? 'دیروز'
                : toJalali($date, 'j F Y'));

        return [
            'date'  => $date,
            'label' => $label,
            'messages' => $this->messages($messages, $authId, $editTimeLimit),
        ];
    }

    public function messages(array $messages, int $authId, int $editTimeLimit): array
    {
        $total = count($messages);

        return array_map(function (array $msg, int $i) use ($messages, $total, $authId, $editTimeLimit) {
            $senderId = (int) ($msg['sender_id'] ?? 0);
            $isMine   = $senderId === $authId;
            $prev     = $i > 0 ? $messages[$i - 1] : null;
            $next     = $i < $total - 1 ? $messages[$i + 1] : null;
            $isFirst  = !$prev || (int) ($prev['sender_id'] ?? 0) !== $senderId;
            $isLast   = !$next || (int) ($next['sender_id'] ?? 0) !== $senderId;
            $createdAt = Carbon::parse($msg['created_at'] ?? now());

            return [
                'id'         => (int) ($msg['id'] ?? 0),
                'body'       => $msg['body'] ?? '',
                'body_html'  => nl2br($this->linkify(e($msg['body'] ?? '')), false),
                'time'       => $createdAt->isToday() ? toJalali($createdAt->toDateTimeString(), 'H:i') : toJalali($createdAt->toDateTimeString(), 'Y/m/d H:i'),
                'datetime'   => $msg['created_at'] ?? '',
                'is_mine'    => $isMine,
                'is_last'    => $isLast,
                'is_edited'  => (bool) ($msg['is_edited'] ?? false),
                'is_read'    => !empty($msg['read_at']),
                'read_at_label' => !empty($msg['read_at']) ? toJalali($msg['read_at'], 'Y/m/d H:i') : null,
                'sender_name' => $msg['sender']['name'] ?? 'ناشناس',
                'sender_avatar' => $msg['sender']['avatar'] ?? null,
                'can_edit'   => $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds(now()) <= $editTimeLimit,
                'can_delete' => $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds(now()) <= $editTimeLimit,
                'attachments' => $this->attachments($msg['attachments'] ?? []),
                'gap_class'  => $isFirst ? 'mt-4' : 'mt-1',
                'bubble_radius' => $this->bubbleRadius($isMine, $isFirst, $isLast),
                'reply_to'   => $this->replyPreview($msg['reply_to'] ?? null),
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
            'sender_name' => $replyTo['sender']['name'] ?? 'ناشناس',
            'body'        => Str::limit($replyTo['body'] ?? '', 50),
        ];
    }

    private function bubbleRadius(bool $isMine, bool $isFirst, bool $isLast): string
    {
        if ($isMine) {
            return match (true) {
                $isFirst && $isLast => 'rounded-2xl',
                $isFirst            => 'rounded-2xl rounded-bl-md',
                $isLast             => 'rounded-2xl rounded-tl-md',
                default             => 'rounded-2xl rounded-l-md',
            };
        }

        return match (true) {
            $isFirst && $isLast => 'rounded-2xl',
            $isFirst            => 'rounded-2xl rounded-br-md',
            $isLast             => 'rounded-2xl rounded-tr-md',
            default             => 'rounded-2xl rounded-r-md',
        };
    }

    private function attachments(array $attachments): array
    {
        return collect($attachments)->map(fn(array $file) => [
            ...$file,
            'url' => self::resolvePublicAssetUrl($file['path']),
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
