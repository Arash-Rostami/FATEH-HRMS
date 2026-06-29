<?php

namespace App\Services\Menu\Notifications;

use App\Models\Message;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class ContactNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        $count = Message::where('sender_id', $subject->id)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return 'شما ' . $count . ' پیام خوانده‌نشده از این فرستنده دارید.';
    }

    public function for($subject)
    {
        return User::active()
            ->whereHas('receivedMessages', fn($query) => $query
                ->where('sender_id', $subject->id)
                ->whereNull('read_at')
            )->get();
    }

    public function getKey(): string
    {
        return 'contacts-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return Message::where('sender_id', $subject->id)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->exists();
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
                'on' => ['created', 'updated', 'deleted'],
                'subject' => fn(Message $message) => $message->sender
            ],
        ];
    }
}
