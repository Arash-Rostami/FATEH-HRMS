<?php

namespace App\Livewire\Dashboard\Contact\Presentation;

use App\Enums\PresenceStatus;
use App\Livewire\Dashboard\Messaging\Actions\ForwardMessageAction;
use App\Models\Concerns\HasAvatar;
use App\Models\Profile;
use App\Traits\Livewire\BuildsChatBubbles;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContactPresenter
{
    use BuildsChatBubbles, HasAvatar;

    public function pinned(?object $contact, array $names): ?array
    {
        return $this->pinPreview(
            $contact?->pinned_message_id ? (int) $contact->pinned_message_id : null,
            $contact?->pinned_body,
            $names[(int) $contact?->pinned_sender_id]['name'] ?? 'ناشناس'
        );
    }

    public function sidebar(array $contacts, int $authId): array
    {
        return array_map(fn (array $c) => $this->contact($c, $authId), $contacts);
    }

    public function contact(array $c, int $authId): array
    {
        $last = $c['last_message'] ?? null;
        $occasion = $c['occasion'] ?? null;

        return [
            'id' => $c['id'],
            'name' => $c['name'],
            'avatar' => $this->resolveImageUrl($c['profile']['image'] ?? null) ?? $this->generateInitialsAvatar($c['name'] ?? null),
            'position' => $c['display_position'] ?? ($c['profile']['position'] ?? 'عضو سازمان'),
            'is_online' => (bool) ($c['is_online'] ?? false),
            'inactive' => (bool) ($c['inactive'] ?? false),
            'presence' => $this->presence($c['presence'] ?? null),
            'focus' => $c['focus'] ?? null,
            'unread' => (int) ($c['unread_count'] ?? 0),
            'occasion' => $occasion,
            'occasion_tone' => $occasion ? Profile::occasionTone($occasion) : null,
            'org_title' => implode(' › ', array_filter([$c['unit'] ?? null, $c['section'] ?? null])),
            'last_message' => $last ? [
                'body' => $this->lastMessagePreview($last),
                'time' => toJalaliRelative($last['created_at'], short: true),
                'datetime' => $last['created_at'],
                'is_mine' => (int) ($last['sender_id'] ?? 0) === $authId,
                'is_read' => ! empty($last['read_at']),
            ] : null,
        ];
    }

    public function firstUnreadId(array $messages, int $authId): ?int
    {
        foreach ($messages as $m) {
            if ((int) ($m['sender_id'] ?? 0) !== $authId && empty($m['read_at']) && empty($m['deleted_at'])) {
                return (int) ($m['id'] ?? 0);
            }
        }

        return null;
    }

    public function messageGroup(string $date, array $messages, int $authId, int $editTimeLimit, ?int $firstUnreadId = null, array $names = []): array
    {
        $day = Carbon::parse($date);

        return [
            'date' => $date,
            'label' => match (true) {
                $day->isToday() => 'امروز',
                $day->isYesterday() => 'دیروز',
                default => toJalali($date, 'j F Y'),
            },
            'relative' => toJalaliRelative($messages[0]['created_at'] ?? $date, false, PHP_INT_MAX),
            'messages' => $this->messages($messages, $authId, $editTimeLimit, $firstUnreadId, $names),
        ];
    }

    public function messages(array $messages, int $authId, int $editTimeLimit, ?int $firstUnreadId = null, array $names = []): array
    {
        $total = count($messages);
        $now = now();

        return array_map(function (array $msg, int $i) use ($messages, $total, $now, $authId, $editTimeLimit, $firstUnreadId, $names) {
            $id = (int) ($msg['id'] ?? 0);
            $body = $msg['body'] ?? '';
            $sender = $msg['sender'] ?? [];
            $senderId = (int) ($msg['sender_id'] ?? 0);
            $isMine = $senderId === $authId;
            $isRead = ! empty($msg['read_at']);
            $prev = $i > 0 ? $messages[$i - 1] : null;
            $next = $i < $total - 1 ? $messages[$i + 1] : null;
            $isFirst = ! $prev || (int) ($prev['sender_id'] ?? 0) !== $senderId;
            $isLast = ! $next || (int) ($next['sender_id'] ?? 0) !== $senderId;
            $createdAt = Carbon::parse($msg['created_at'] ?? now())->setTimezone(config('app.timezone'));
            $canModify = $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds($now) <= $editTimeLimit;

            $parts = ForwardMessageAction::parts($body);

            return [
                'id' => $id,
                'body' => $body,
                'body_html' => nl2br($this->linkify(e($parts['note'])), false),
                'forwarded_html' => $parts['forwarded'] === null ? null : nl2br($this->linkify(e($parts['forwarded'])), false),
                'plain_text' => $parts['forwarded'] === null ? $body : ($parts['note'] !== '' ? $parts['note'] : $parts['forwarded']),
                'time' => toJalali($createdAt->toDateTimeString(), $createdAt->isToday() ? 'H:i' : 'Y/m/d H:i'),
                'datetime' => $msg['created_at'] ?? '',
                'is_mine' => $isMine,
                'is_last' => $isLast,
                'is_edited' => (bool) ($msg['is_edited'] ?? false),
                'is_read' => $isRead,
                'is_new_messages' => $firstUnreadId !== null && $id === $firstUnreadId,
                'read_at_label' => $isRead ? toJalali($msg['read_at'], 'Y/m/d H:i') : null,
                'sender_name' => $sender['name'] ?? 'ناشناس',
                'sender_avatar' => $sender['avatar'] ?? null,
                'sender_online' => (bool) ($sender['online'] ?? false),
                'sender_presence' => $this->presence($sender['presence'] ?? null),
                'sender_focus' => $sender['focus'] ?? null,
                'sender_inactive' => (bool) ($sender['inactive'] ?? false),
                'can_edit' => $canModify,
                'can_delete' => $canModify,
                'attachments' => $this->attachments($msg['attachments'] ?? []),
                'reactions' => $this->reactionChips($msg['reactions'] ?? [], $authId, $names),
                'type_flags' => $this->typeFlags($msg),
                'gap_class' => $isFirst ? 'mt-4' : 'mt-1',
                'bubble_radius' => $this->bubbleRadius($isMine, $isFirst, $isLast),
                'reply_to' => $this->replyPreview($msg['reply_to'] ?? null),
                'animation_delay' => min($total - 1 - $i, 10) * 0.04,
            ];
        }, array_values($messages), array_keys($messages));
    }

    private function presence(mixed $value): ?PresenceStatus
    {
        return $value instanceof PresenceStatus ? $value : null;
    }

    private function lastMessagePreview(array $last): string
    {
        return Str::limit(
            trim((string) $last['body']) !== '' ? $last['body'] : (filled($last['attachments'] ?? []) ? 'پیوست' : ''),
            30
        );
    }
}
