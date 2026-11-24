<?php

namespace App\Livewire\Dashboard\Channel\Presentation;

use App\Models\Channel;
use App\Support\ChannelAccessPolicy;
use App\Traits\Livewire\BuildsMessageGroups;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

class ChannelPresenter
{
    use BuildsMessageGroups;

    public function headerState(?Channel $channel, array $header, ?array $typingMembers, array $members): array
    {
        $typingMembers ??= [];
        $lastMsgAt = $channel?->getAttribute('last_message_at');

        return [
            'isOwner' => ChannelAccessPolicy::isOwner($channel, auth()->user()),
            'canLeave' => $channel !== null && ChannelAccessPolicy::canLeave($channel, (int) auth()->id()),
            'typingMembers' => $typingMembers,
            'typingCount' => count($typingMembers),
            'channelIdJs' => Js::from($header['id']),
            'onlineCount' => collect($members)->reject(fn (array $m) => $m['inactive'])->where('is_online', true)->count(),
            'lastActivity' => $lastMsgAt && Carbon::parse($lastMsgAt)->diffInMinutes(now()) >= 5
                ? toJalaliRelative($lastMsgAt, dateAfterDays: 0)
                : null,
        ];
    }

    public function pinned(?Channel $channel, array $readersMap): ?array
    {
        return $this->pinPreview(
            $channel?->pinned_message_id ? (int) $channel->pinned_message_id : null,
            $channel?->pinned_body,
            $readersMap[(int) $channel?->pinned_sender_id]['name'] ?? 'ناشناس'
        );
    }

    public function sidebar(array $channels, int $authId): array
    {
        return array_map(fn (array $c) => $this->channel($c, $authId), $channels);
    }

    public function channel(array $c, int $authId): array
    {
        $last = $c['last_message'] ?? null;

        return [
            'id' => (int) $c['id'],
            'name' => $c['name'],
            'slug' => $c['slug'],
            'slug_handle' => $this->slugHandle($c['slug'] ?? ''),
            'description' => $c['description'] ?? '',
            'type' => $c['type'] ?? 'open',
            'initials' => mb_substr($c['name'] ?? '', 0, 1),
            'unread' => (int) ($c['unread_count'] ?? 0),
            'members_count' => (int) ($c['members_count'] ?? 0),
            'is_entered' => (bool) ($c['is_entered'] ?? false),
            'last_message' => $last ? [
                'body' => $this->lastMessagePreview($last),
                'sender_name' => $last['sender_name'] ?? null,
                'time' => toJalaliRelative($last['created_at'] ?? null, short: true),
                'datetime' => $last['created_at'] ?? null,
                'is_mine' => (int) ($last['sender_id'] ?? 0) === $authId,
            ] : null,
        ];
    }

    public function channelHeader(Channel $channel): array
    {
        $type = $channel->type;

        return [
            'id' => (int) $channel->id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'slug_handle' => $this->slugHandle($channel->slug ?? ''),
            'description' => $channel->description ?? '',
            'type' => $type->value,
            'type_label' => $type->getLabel(),
            'type_icon' => $type->getMaterialIcon(),
            'type_color' => $type->getMaterialColor(),
            'owner_name' => $channel->owner?->name ?? '—',
            'members_count' => (int) ($channel->members_count ?? 0),
        ];
    }

    public function memberTabs(): array
    {
        return [
            ['id' => 'members', 'icon' => 'group', 'label' => 'اعضا', 'alpine' => "\$wire.memberTab = 'members'", 'activeExpr' => "\$wire.memberTab === 'members'"],
            ['id' => 'writers', 'icon' => 'edit_note', 'label' => 'اجازهٔ نوشتن', 'alpine' => "\$wire.memberTab = 'writers'", 'activeExpr' => "\$wire.memberTab === 'writers'"],
        ];
    }

    public function slugHandle(string $slug): string
    {
        $slug = trim((string) $slug);

        return match (true) {
            $slug === '' => '',
            (bool) preg_match('/^\p{Arabic}/u', $slug) => $slug.'#',
            default => '#'.$slug,
        };
    }

    public function browseList(array $channels): array
    {
        return array_map(fn (array $c) => [
            'id' => (int) $c['id'],
            'name' => $c['name'],
            'slug' => $c['slug'] ?? '',
            'slug_handle' => $this->slugHandle($c['slug'] ?? ''),
            'description' => $c['description'] ?? '',
            'type' => $c['type'] ?? 'open',
            'owner_name' => $c['owner_name'] ?? '—',
        ], $channels);
    }

    private function lastMessagePreview(array $last): string
    {
        $body = $last['body'] ?? null;

        return Str::limit(
            trim((string) $body) !== '' ? $body : (filled($last['attachments'] ?? null) ? 'پیوست' : ''),
            30
        );
    }
}