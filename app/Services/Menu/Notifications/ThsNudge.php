<?php

namespace App\Services\Menu\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class ThsNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return match ($subject->status) {
            'open' => 'تیکت جدیدی به واحد شما ارجاع شده است؛ برای بررسی به بخش تیکتینگ مراجعه کنید.',
            'in-progress' => 'تیکتی به شما محول شده است؛ برای اقدام به بخش تیکتینگ مراجعه کنید.',
            'closed' => 'پاسخ تیکت شما ثبت شد؛ برای مشاهده نتیجه به بخش تیکتینگ مراجعه کنید.',
            default => 'تیکت به‌روزرسانی شد؛ برای مشاهده به بخش تیکتینگ مراجعه کنید.',
        };
    }

    public function for($subject): Collection
    {
        $recipient = $subject->currentActionRecipient();

        return $recipient ? collect([$recipient]) : collect();
    }

    public function getKey(): string
    {
        return 'ths-controller:nudge';
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
        return match ($subject->status) {
            'open' => 'تیکت جدید ارجاع‌شده به واحد شما',
            'in-progress' => 'تیکت محول‌شده به شما',
            'closed' => 'پاسخ تیکت ثبت شد',
            default => 'به‌روزرسانی تیکت',
        };
    }

    public function triggers(): array
    {
        return [
            ['class' => Ticket::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }
}