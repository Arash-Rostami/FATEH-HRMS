<?php

namespace App\Services\Menu\Notifications;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class ChannelNudge implements MenuNudge
{
    private array $invited = [];

    private array $unread = [];

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return isset($this->invited[$user->id])
            ? "شما به کانال «{$subject->name}» دعوت شده‌اید. برای ورود روی آن کلیک کنید."
            : 'شما ' . ($this->unread[$user->id] ?? 0) . ' پیام خوانده‌نشده در این کانال دارید.';
    }

    public function for($subject): Collection
    {
        $this->invited = array_flip(Channel::invitedUserIds($subject->id));
        $this->unread = Channel::unreadCountsFor($subject->id);

        return User::active()->whereIn('id', array_unique([...array_keys($this->invited), ...array_keys($this->unread)]))->get();
    }

    public function getKey(): string
    {
        return 'channels-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return true;
    }

    public function title($subject, User $user): string
    {
        $prefix = isset($this->invited[$user->id])
            ? 'دعوت به کانال'
            : 'پیام جدید در کانال';

        return $prefix . ': ' . $subject->name;
    }

    public function triggers(): array
    {
        return [
            ['class' => Channel::class, 'on' => ['deleted', 'forceDeleted'], 'subject' => null],
            ['class' => ChannelMessage::class, 'on' => ['created', 'deleted'], 'subject' => fn(ChannelMessage $message) => $message->channel],
        ];
    }
}
