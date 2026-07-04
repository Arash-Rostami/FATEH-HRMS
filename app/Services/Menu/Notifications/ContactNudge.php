<?php

namespace App\Services\Menu\Notifications;

use App\Models\Message;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class ContactNudge implements MenuNudge
{
    private array $unreadCountCache = [];

    public function body($subject, User $user): string
    {
        $count = $this->unreadCountCache[$user->id] ?? 0;

        return 'شما ' . $count . ' پیام خوانده‌نشده از این فرستنده دارید.';
    }

    public function for($subject)
    {
        $this->unreadCountCache = Message::unreadCountsFrom($subject->id);

        if (empty($this->unreadCountCache)) {
            return collect();
        }

        return User::active()
            ->whereIn('id', array_keys($this->unreadCountCache))
            ->get();
    }

    public function getKey(): string
    {
        return 'contacts-controller:nudge';
    }

    public function badgeSuppressesCreate(): bool
    {
        return false;
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
        return 'پیام جدید از: ' . ($subject->name ?? 'کاربر');
    }

    public function triggers(): array
    {
        return [
            [
                'class' => Message::class,
                'on' => ['created', 'updated', 'deleted', 'forceDeleted', 'restored'],
                'subject' => fn(Message $message) => $message->sender
            ],
        ];
    }
}
