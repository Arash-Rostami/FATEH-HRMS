<?php

namespace App\Livewire\Dashboard\Channel\Presentation;

use App\Livewire\Dashboard\Messaging\Actions\ForwardMessageAction;
use App\Models\Channel;
use App\Traits\Livewire\BuildsMessageGroups;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

class ChannelPresenter
{
    use BuildsMessageGroups;

    public function headerState(?Channel $channel, array $header, ?array $typingMembers, array $members, array $channels): array
    {
        $typing = $typingMembers ?? [];
        $lastMsgAt = collect($channels)->firstWhere('id', $header['id'])['last_message']['created_at'] ?? null;

        return [
            'isOwner' => $channel
                && $channel->owner_id !== null
                && auth()->check()
                && (int) $channel->owner_id === (int) auth()->id(),
            'typingMembers' => $typing,
            'typingCount' => count($typing),
            'channelIdJs' => Js::from($header['id']),
            'onlineCount' => collect($members)->reject(fn (array $m) => $m['inactive'])->where('is_online', true)->count(),
            'lastActivity' => $lastMsgAt && Carbon::parse($lastMsgAt)->diffInMinutes(now()) >= 5
                ? toJalaliRelative($lastMsgAt, dateAfterDays: 0)
                : null,
        ];
    }

    public function pinned(?Channel $channel, array $readersMap): ?array
    {
        $body = $channel?->pinned_message_id ? $channel->pinned_body : null;

        if ($body === null) {
            return null;
        }

        $parts = ForwardMessageAction::parts($body);
        $text = $parts['forwarded'] === null ? $body : ($parts['note'] !== '' ? $parts['note'] : $parts['forwarded']);

        return [
            'id' => (int) $channel->pinned_message_id,
            'sender' => $readersMap[(int) $channel->pinned_sender_id]['name'] ?? 'ناشناس',
            'body' => $text !== '' ? Str::limit($text, 80) : 'پیوست',
        ];
    }

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
            'members_count' => (int)($c['members_count'] ?? 0),
            'is_entered' => !empty($c['is_entered']),
            'last_message' => $last ? [
                'body' => Str::limit(trim((string) ($last['body'] ?? '')) !== '' ? $last['body'] : (filled($last['attachments'] ?? []) ? 'پیوست' : ''), 30),
                'sender_name' => $last['sender_name'] ?? null,
                'time' => toJalaliRelative($last['created_at'] ?? null, short: true),
                'datetime' => $last['created_at'] ?? null,
                'is_mine' => (int)($last['sender_id'] ?? 0) === $authId,
            ] : null,
        ];
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
}