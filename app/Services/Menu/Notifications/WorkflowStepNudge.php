<?php

namespace App\Services\Menu\Notifications;

use App\Models\User;
use App\Models\Workflow;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class WorkflowStepNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        $projectName = $subject->project?->name;

        if ($subject->isCompleted()) {
            return "چرخهٔ «{$subject->name}» در پروژهٔ «{$projectName}» با موفقیت به پایان رسید.";
        }

        $stepLabel = $subject->currentStep()['label'] ?? '';

        return "نوبت مرحلهٔ «{$stepLabel}» از چرخهٔ «{$subject->name}» در پروژهٔ «{$projectName}» به شما رسیده است؛ برای مشاهده کلیک کنید.";
    }

    public function for($subject): Collection
    {
        if ($subject->isCompleted()) {
            return $subject->owner ? collect([$subject->owner]) : collect();
        }

        $ids = $subject->currentStep()['assignee_user_ids'] ?? [];
        $driverId = ($subject->currentStep()['populate_task'] ?? false) ? ($subject->currentStep()['assignee_user_ids'][0] ?? null) : null;

        return User::active()->whereIn('id', $ids)->get()->reject(fn ($u) => $u->id === $driverId);
    }

    public function getKey(): string
    {
        return 'workflow:nudge';
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
        return $subject->isCompleted() ? "چرخه «{$subject->name}» تکمیل شد" : 'نوبت شماست: ' . ($subject->currentStep()['label'] ?? '');
    }

    public function triggers(): array
    {
        return [
            ['class' => Workflow::class, 'on' => ['updated'], 'subject' => function (Workflow $wf) {
                $stepChanged = $wf->wasChanged('current_step') && $wf->isActive();
                $justCompleted = $wf->wasChanged('status') && $wf->isCompleted();

                if (($stepChanged || $justCompleted) && !$wf->projectWithTrashed()?->trashed()) return $wf;

                return null;
            }],
        ];
    }

    public function url($subject): ?string
    {
        return route('projects', ['open' => $subject->project_id, 'tab' => 'workflow']);
    }
}
