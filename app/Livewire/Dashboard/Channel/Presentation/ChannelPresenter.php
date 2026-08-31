<?php

namespace App\Livewire\Dashboard\Channel\Presentation;

use App\Models\Channel;
use App\Traits\BuildsMessageGroups;
use Illuminate\Support\Str;

class ChannelPresenter
{
    use BuildsMessageGroups;

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
}