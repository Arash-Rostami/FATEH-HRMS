<?php

namespace App\Services\Menu\Notifications;

use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class ThsNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        if ($subject->currentActionRecipient()?->is($user)) {
            return match ($subject->status) {
                'open' => 'تیکت جدیدی به واحد شما ارجاع شده است؛ برای بررسی به بخش تیکتینگ مراجعه کنید.',
                'in-progress' => 'تیکتی به شما محول شده است؛ برای اقدام به بخش تیکتینگ مراجعه کنید.',
                'closed' => 'پاسخ تیکت شما ثبت شد؛ برای مشاهده نتیجه به بخش تیکتینگ مراجعه کنید.',
                default => 'تیکت به‌روزرسانی شد؛ برای مشاهده به بخش تیکتینگ مراجعه کنید.',
            };
        }

        return 'پاسخ جدیدی برای تیکت شما ثبت شده است؛ برای مشاهده به بخش تیکتینگ مراجعه کنید.';
    }

    public function for($subject): Collection
    {
        $recipients = collect();

        if ($lifecycle = $subject->currentActionRecipient()) {
            $recipients->push($lifecycle);
        }

        $otherPartyIds = $subject->otherReplyParticipants([$subject->requester_id, $subject->assigned_to]);

        if ($otherPartyIds) {
            $recipients = $recipients->merge(User::whereIn('id', $otherPartyIds)->get());
        }

        return $recipients->unique('id')->values();
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
        $latestReply = $subject->latestReply();

        if ($latestReply && (int)$latestReply->user_id === $user->id) {
            return (bool)$subject->currentActionRecipient()?->is($user);
        }

        return true;
    }

    public function title($subject, User $user): string
    {
        if ($subject->currentActionRecipient()?->is($user)) {
            return match ($subject->status) {
                'open' => 'تیکت جدید ارجاع‌شده به واحد شما',
                'in-progress' => 'تیکت محول‌شده به شما',
                'closed' => 'پاسخ تیکت ثبت شد',
                default => 'به‌روزرسانی تیکت',
            };
        }

        return 'پاسخ جدید: ' . $subject->request_subject;
    }

    public function triggers(): array
    {
        return [
            ['class' => Ticket::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
            ['class' => Reply::class, 'on' => ['created'], 'subject' => fn(Reply $reply) =>
                $reply->repliable_type === Ticket::class ? $reply->repliable : null],
        ];
    }

    public function url($subject): ?string
    {
        return route('ths', ['open' => $subject->getKey()]);
    }
}
