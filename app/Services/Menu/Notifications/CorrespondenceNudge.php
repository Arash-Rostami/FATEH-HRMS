<?php

namespace App\Services\Menu\Notifications;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class CorrespondenceNudge implements MenuNudge
{
    private const URGENT = [Correspondence::URGENCY_URGENT, Correspondence::URGENCY_IMMEDIATE];

    private array $plan = [];

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return $this->plan[$user->id][1];
    }

    public function for($subject): Collection
    {
        $this->plan = $this->plan($subject);

        return $this->plan === []
            ? collect()
            : User::visibleOnBoard()->whereIn('id', array_keys($this->plan))->get();
    }

    public function getKey(): string
    {
        return 'correspondence-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return isset($this->plan[$user->id]);
    }

    public function title($subject, User $user): string
    {
        return $this->plan[$user->id][0];
    }

    public function triggers(): array
    {
        return [
            ['class' => Correspondence::class, 'on' => ['created', 'updated', 'deleted', 'restored', 'forceDeleted'], 'subject' => fn (Correspondence $letter) => !$letter->exists
                || $letter->trashed()
                || $letter->wasRecentlyCreated
                || $letter->wasChanged(['status', 'awaiting_user_id', 'parties', 'user_id', 'subject', 'classification', 'urgency', 'route_mode', 'deleted_at'])
                    ? $letter
                    : null],
        ];
    }

    public function url($subject): ?string
    {
        return route('correspondence', ['open' => $subject->getKey()]);
    }

    private function plan(Correspondence $letter): array
    {
        return match ($letter->status) {
            Correspondence::STATUS_SHARED => $this->signaturePlan($letter),
            Correspondence::STATUS_APPROVED => $this->approvedPlan($letter),
            Correspondence::STATUS_REJECTED => $this->authorPlan($letter, 'رد شد: ', 'این نامه رد شد.'),
            Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED => $this->routedPlan($letter),
            default => [],
        };
    }

    private function routedPlan(Correspondence $letter): array
    {
        $plan = [];

        foreach ($letter->parties ?? [] as $party) {
            $userId = is_array($party) ? (int) ($party['user_id'] ?? 0) : 0;

            if ($userId > 0 && $userId !== (int) $letter->user_id && blank($party['opened_at'] ?? null)) {
                $plan[$userId] = ['نامهٔ وارده برای شما ارجاع شد', 'نامهٔ «' . $this->label($letter) . '» برای اطلاع شما ثبت یا ارجاع شده است.'];
            }
        }

        return $plan;
    }

    private function signaturePlan(Correspondence $letter): array
    {
        $candidates = [];

        if (filled($letter->awaiting_user_id)) {
            $candidates[] = (int) $letter->awaiting_user_id;
        }

        foreach ($letter->parties ?? [] as $party) {
            $userId = is_array($party) ? (int) ($party['user_id'] ?? 0) : 0;

            if ($userId > 0 && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER) {
                $candidates[] = $userId;
            }
        }

        $plan = [];

        foreach (array_unique($candidates) as $userId) {
            if ($userId !== (int) $letter->user_id && CorrespondencePresenter::waitingForMe($letter, $userId)) {
                $plan[$userId] = ['امضای نامه در انتظار شماست', 'نامهٔ «' . $this->label($letter) . '» برای امضای شما ارسال شده است.'];
            }
        }

        return $plan;
    }

    private function approvedPlan(Correspondence $letter): array
    {
        $plan = $this->authorPlan($letter, 'تأیید شد: ', 'این نامه تأیید شد.');

        if (in_array($letter->urgency, self::URGENT, true)) {
            foreach ($letter->parties ?? [] as $party) {
                $userId = is_array($party) ? (int) ($party['user_id'] ?? 0) : 0;

                if ($userId > 0 && ($party['role'] ?? null) === LetterForm::ROLE_FYI && !isset($plan[$userId])) {
                    $plan[$userId] = ['تأیید شد: ' . $this->label($letter), 'این نامه تأیید شد.'];
                }
            }
        }

        return $plan;
    }

    private function authorPlan(Correspondence $letter, string $title, string $body): array
    {
        return (int) $letter->user_id > 0
            ? [(int) $letter->user_id => [$title . $this->label($letter), $body]]
            : [];
    }

    private function label(Correspondence $letter): string
    {
        if ($letter->classification !== Correspondence::CLASSIFICATION_NORMAL) {
            return __('resources/correspondence/strings.confidential_subject');
        }

        return $letter->subject ?: 'بدون موضوع';
    }
}
