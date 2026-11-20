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
        if ($subject->isActionRecipient($user)) {
            return match ($subject->status) {
                'open' => 'درخواست تازه‌ای به واحد شما رسیده است. برای تعیین مسئول رسیدگی، بخش تیکتینگ را باز کنید.',
                'in-progress' => 'شما مسئول رسیدگی به یک درخواست شده‌اید. برای دیدن جزئیات، بخش تیکتینگ را باز کنید.',
                'closed' => 'درخواست شما بسته شد. نتیجه را در بخش تیکتینگ ببینید و امتیاز رضایت خود را ثبت کنید.',
                default => 'درخواست پشتیبانی تغییر کرده است. برای دیدن جزئیات، بخش تیکتینگ را باز کنید.',
            };
        }

        return 'پاسخ تازه‌ای برای درخواست مرتبط با شما ثبت شده است. برای خواندن آن، بخش تیکتینگ را باز کنید.';
    }

    public function for($subject): Collection
    {
        $recipients = $subject->currentActionRecipients();

        $otherPartyIds = $subject->status === 'closed'
            ? []
            : $subject->otherReplyParticipants([$subject->requester_id, $subject->assigned_to]);

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
        if ($subject->status === 'closed' && $subject->satisfaction_score !== null) {
            return false;
        }

        $latestReply = $subject->latestReply();

        if ($latestReply && (int)$latestReply->user_id === $user->id) {
            return $subject->isActionRecipient($user);
        }

        return true;
    }

    public function title($subject, User $user): string
    {
        if ($subject->isActionRecipient($user)) {
            return match ($subject->status) {
                'open' => 'درخواست تازه برای واحد شما',
                'in-progress' => 'مسئول رسیدگی به درخواست شدید',
                'closed' => 'درخواست شما بسته شد',
                default => 'درخواست پشتیبانی تغییر کرد',
            };
        }

        return 'پاسخ جدید: ' . $subject->request_subject;
    }

    public function triggers(): array
    {
        return [
            ['class' => Ticket::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }

    public function url($subject): ?string
    {
        return route('ths', ['open' => $subject->getKey()]);
    }
}
