<?php

namespace App\Services\Menu\Notifications;

use App\Models\AiReport;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class AiReportNudge implements MenuNudge
{
    private const WATCHED = ['status', 'recipients', 'seen_by', 'active', 'saved_at'];

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return e($subject->user?->name ?? '') . ' گزارش «' . e($subject->title ?? '') . '» را با شما به اشتراک گذاشت.';
    }

    public function for($subject): Collection
    {
        if (!$this->shared($subject)) {
            return collect();
        }

        $subject->loadMissing('user:id,name');

        return User::active()
            ->whereIn('id', array_map('intval', $subject->recipients))
            ->where('id', '!=', $subject->user_id)
            ->get(['id']);
    }

    public function getKey(): string
    {
        return 'ai-report-controller:nudge';
    }

    public function refresh(): bool
    {
        return false;
    }

    public function show($subject, User $user): bool
    {
        return $this->shared($subject)
            && !in_array($user->id, array_map('intval', $subject->seen_by ?? []), true);
    }

    public function title($subject, User $user): string
    {
        return 'گزارشی در گزارش ساز هوشمند با شما به اشتراک گذاشته شد';
    }

    public function triggers(): array
    {
        return [
            ['class' => AiReport::class, 'on' => ['updated', 'deleted'], 'subject' => fn (AiReport $report) => !$report->exists || $report->wasChanged(self::WATCHED) ? $report : null],
        ];
    }

    public function url($subject): ?string
    {
        return URL::temporarySignedRoute('ai-report.shared', now()->addDays((int) config('ai.report.share_link_days')), ['report' => $subject->getKey()]);
    }

    private function shared(AiReport $report): bool
    {
        return $report->active
            && $report->status === AiReport::STATUS_READY
            && $report->saved_at !== null
            && filled($report->recipients);
    }
}
