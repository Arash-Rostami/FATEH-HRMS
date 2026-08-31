<?php

namespace App\Services\Menu\Toasts;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\User;
use App\Services\Menu\Contracts\MenuEdge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChannelToast implements MenuEdge
{
    private array $invited = [];

    private array $mentioned = [];

    public function getKey(): string
    {
        return 'channels-controller:edge';
    }

    public function for($subject): Collection
    {
        $this->invited = array_flip(Channel::invitedUserIds($subject->id));
        $this->mentioned = $this->mentionedSenders($subject);

        $ids = array_unique([...array_keys($this->invited), ...array_keys($this->mentioned)]);

        return $ids === [] ? collect() : User::active()->whereIn('id', $ids)->get();
    }

    public function title($subject, User $user): string
    {
        if (isset($this->invited[$user->id])) {
            return 'دعوت به کانال: ' . $subject->name;
        }

        return 'شما را منشن کرده است';
    }

    public function body($subject, User $user): string
    {
        if (isset($this->invited[$user->id])) {
            return "شما به کانال «{$subject->name}» دعوت شده‌اید. برای ورود روی آن کلیک کنید.";
        }

        return $this->mentioned[$user->id] ?? $subject->name;
    }

    public function icon($subject, User $user): string
    {
        return isset($this->invited[$user->id]) ? 'mail' : 'alternate_email';
    }

    public function url($subject): ?string
    {
        return route('channels', ['open' => $subject->getKey()]);
    }

    public function triggers(): array
    {
        return [
            ['class' => Channel::class, 'on' => ['deleted', 'forceDeleted'], 'subject' => null],
            ['class' => ChannelMember::class, 'on' => ['created', 'deleted'], 'subject' => fn(ChannelMember $cm) => Channel::find($cm->channel_id)],
            ['class' => ChannelMember::class, 'on' => ['updated'], 'subject' => fn(ChannelMember $cm) => $cm->wasChanged('entered_at') ? Channel::find($cm->channel_id) : null],
            ['class' => ChannelMessage::class, 'on' => ['created', 'deleted'], 'subject' => fn(ChannelMessage $m) => $m->channel],
        ];
    }

    private function mentionedSenders(Channel $channel): array
    {
        $members = DB::table('channel_members')
            ->join('users', 'users.id', '=', 'channel_members.user_id')
            ->where('channel_members.channel_id', $channel->id)
            ->whereNotNull('channel_members.entered_at')
            ->get(['channel_members.user_id', 'channel_members.last_read_message_id', 'users.name']);

        if ($members->isEmpty()) {
            return [];
        }

        $patterns = [];
        foreach ($members as $member) {
            if ($member->name === null || $member->name === '') {
                continue;
            }
            $patterns[(int) $member->user_id] = [
                '/(?<![\w@])@' . preg_quote($member->name, '/') . '(?![\p{L}\p{N}_])/u',
                (int) ($member->last_read_message_id ?? 0),
            ];
        }

        if ($patterns === []) {
            return [];
        }

        $messages = ChannelMessage::withoutTrashed()
            ->where('channel_id', $channel->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'sender_id', 'body']);

        $matched = [];

        foreach ($messages as $m) {
            if ($patterns === []) {
                break;
            }

            $senderId = (int) $m->sender_id;
            $messageId = (int) $m->id;
            $body = (string) $m->body;

            foreach ($patterns as $uid => [$pattern, $lastRead]) {
                if ($uid === $senderId || $messageId <= $lastRead) {
                    continue;
                }
                if (preg_match($pattern, $body)) {
                    $matched[$uid] = $senderId;
                    unset($patterns[$uid]);
                }
            }
        }

        if ($matched === []) {
            return [];
        }

        $senders = User::whereIn('id', array_unique($matched))->pluck('name', 'id');

        return array_map(fn($senderId) => $senders[$senderId] ?? '—', $matched);
    }
}
