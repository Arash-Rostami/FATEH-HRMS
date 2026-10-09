<?php

namespace App\Services\Menu\Notifications;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use App\Services\ProjectTask\TaskMetrics;
use Illuminate\Support\Collection;

class TaskNudge implements MenuNudge
{
    private const QUIET = [
        'archive', 'deadline_change', 'priority_change', 'label_change', 'project_change',
        'meta_change', 'settings_change', 'workflow_step', 'department_change',
    ];

    private array $plan = [];

    public function __construct(private readonly string $kind = 'activity')
    {
    }

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function getKey(): string
    {
        return match ($this->kind) {
            'overdue' => 'tasks-controller:overdue-nudge',
            'approval' => 'tasks-controller:approval-nudge',
            default => 'tasks-controller:nudge',
        };
    }

    public function triggers(): array
    {
        $events = $this->kind === 'activity'
            ? ['created', 'updated', 'deleted', 'restored', 'forceDeleted']
            : ['updated', 'deleted', 'restored', 'forceDeleted'];

        $triggers = [['class' => Task::class, 'on' => $events, 'subject' => null]];

        if ($this->kind === 'activity') {
            $triggers[] = ['class' => Reply::class, 'on' => ['created'], 'subject' => fn(Reply $reply) =>
                $reply->repliable_type === Task::class ? $reply->repliable : null];
        }

        return $triggers;
    }

    public function for($subject): Collection
    {
        $this->plan = match ($this->kind) {
            'overdue' => $this->overduePlan($subject),
            'approval' => $this->approvalPlan($subject),
            default => $this->activityPlan($subject),
        };

        return $this->plan === [] ? collect() : User::active()->whereIn('id', array_keys($this->plan))->get();
    }

    public function show($subject, User $user): bool
    {
        return isset($this->plan[$user->id]);
    }

    public function title($subject, User $user): string
    {
        return $this->plan[$user->id][0];
    }

    public function body($subject, User $user): string
    {
        return $this->plan[$user->id][1];
    }

    public function refresh(): bool
    {
        return true;
    }

    public function url($subject): ?string
    {
        return route('tasks', ['open' => $subject->getKey()]);
    }

    private function overduePlan(Task $task): array
    {
        $days = $this->overdueDays($task);

        if ($days === null) {
            return [];
        }

        $doerId = $task->assigned_to ?? $task->user_id;
        $plan = [$doerId => ['وظیفهٔ سررسید گذشته: ' . $task->title, "وظیفهٔ «{$task->title}» از سررسید خود گذشته است."]];

        $accountableId = $task->detail?->responsible_user_id;
        $limit = $task->project?->charter()->escalationDays() ?? 0;

        if ($accountableId && (int)$accountableId !== (int)$doerId && $limit > 0 && $days >= $limit) {
            $plan[$accountableId] = [
                'تأخیر طولانی وظیفه: ' . $task->title,
                convertToPersian($days) . ' روز از سررسید گذشته و وظیفه هنوز انجام نشده است.',
            ];
        }

        return $plan;
    }

    private function overdueDays(Task $task): ?int
    {
        if ($task->deadline === null || $task->archived_at !== null || in_array($task->status, TaskMetrics::SETTLED_STATUSES, true)) {
            return null;
        }

        $task->loadMissing(['detail:id,task_id,state,responsible_user_id', 'project:id,settings,owner_id']);

        if ($task->isSuspended()) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $task->deadline->copy()->startOfDay();

        return $due->lt($today) ? (int)$due->diffInDays($today, true) : null;
    }

    private function approvalPlan(Task $task): array
    {
        $task->loadMissing(['detail:id,task_id,responsible_user_id', 'project:id,settings,owner_id']);

        if (!$task->isPendingApproval()) {
            return [];
        }

        $ownerId = $task->project?->owner_id;
        $accountableId = $task->detail?->responsible_user_id;
        $active = User::active()->whereIn('id', array_filter([$ownerId, $accountableId]))->pluck('id')->all();
        $approverId = in_array($ownerId, $active, true) ? $ownerId : (in_array($accountableId, $active, true) ? $accountableId : null);

        if ($approverId === null) {
            return [];
        }

        $hours = ($task->completed_at ?? $task->updated_at)->diffInHours(now());
        $prefix = match (true) {
            $hours > 48 => '⏰ فوری: ',
            $hours > 24 => 'یادآوری: ',
            default => '',
        };

        return [$approverId => [
            $prefix . 'وظیفهٔ منتظر تأیید: ' . $task->title,
            "وظیفهٔ «{$task->title}» در ستون انجام است و منتظر تأیید شماست.",
        ]];
    }

    private function activityPlan(Task $task): array
    {
        $replies = Reply::query()
            ->where('repliable_type', Task::class)
            ->where('repliable_id', $task->id)
            ->where(fn($q) => $q->whereNull('type')->orWhereNotIn('type', self::QUIET))
            ->orderByDesc('id')
            ->limit(4)
            ->get(['id', 'user_id', 'type', 'payload', 'created_at']);

        $task->loadMissing(['detail:id,task_id,collaborators,responsible_user_id,state', 'project:id,settings,owner_id']);

        foreach ($replies as $index => $reply) {
            if ($kind = $this->classify($reply, $replies->get($index + 1), $task)) {
                return $this->planFor($kind, $reply, $task);
            }
        }

        return [];
    }

    private function classify(Reply $reply, ?Reply $older, Task $task): ?string
    {
        $payload = $reply->payload ?? [];

        return match ($reply->type) {
            null, TaskActivityType::Comment, TaskActivityType::Attachment => 'reply',
            TaskActivityType::StatusChange => match (true) {
                ($payload['to'] ?? null) === 'done' => 'completed',
                ($payload['from'] ?? null) === 'done' => $this->wasSentBack($reply, $task) ? 'sent_back' : 'reopened',
                default => 'status',
            },
            TaskActivityType::Approval => ($payload['approved'] ?? false) && empty($payload['system'])
                ? ($this->isCompletionPair($reply, $older) ? 'completed' : 'approved')
                : null,
            TaskActivityType::Assignment => match (true) {
                isset($payload['created']) => 'created',
                array_key_exists('from', $payload) => 'assigned',
                isset($payload['added']) => 'helpers',
                default => null,
            },
            TaskActivityType::ResponsibleChange => 'accountable',
            TaskActivityType::StateChange => ($payload['to'] ?? null) === TaskState::Suspension->value ? 'suspended' : null,
            default => null,
        };
    }

    private function isCompletionPair(Reply $approval, ?Reply $older): bool
    {
        return $older?->type === TaskActivityType::StatusChange
            && ($older->payload['to'] ?? null) === 'done'
            && $older->user_id === $approval->user_id
            && $older->created_at->equalTo($approval->created_at);
    }

    private function wasSentBack(Reply $reply, Task $task): bool
    {
        return $reply->user_id !== null
            && (int)$reply->user_id === (int)$task->project?->owner_id
            && (int)$reply->user_id !== (int)($task->assigned_to ?? $task->user_id)
            && $task->project->setting('requires_approval') === true;
    }

    private function planFor(string $kind, Reply $reply, Task $task): array
    {
        $payload = $reply->payload ?? [];
        $actor = $reply->user_id;
        $title = $task->title;
        $doerId = $task->assigned_to ?? $task->user_id;

        $entries = match ($kind) {
            'created' => $this->createdEntries($task, $payload, $title),
            'assigned' => $this->assignedEntries($task, $payload, $title),
            'helpers' => $this->helperEntries($payload, $title),
            'accountable' => $this->accountableEntries($payload, $title),
            'suspended' => [$doerId => ['وظیفه متوقف شد: ' . $title, 'این وظیفه موقتاً متوقف شده است.']],
            default => $this->broadcastEntries($kind, $task, $payload, $title),
        };

        if ($actor !== null) {
            unset($entries[$actor]);
        }

        if ($kind === 'completed' && $task->isPendingApproval() && $doerId && (int)$doerId !== (int)$actor) {
            $entries[$doerId] = ['در انتظار تأیید مدیر: ' . $title, 'وظیفه پس از تأیید مدیر پروژه نهایی می‌شود.'];
        }

        return $entries;
    }

    private function createdEntries(Task $task, array $payload, string $title): array
    {
        $doerId = $payload['to'] ?? null;
        $accountableId = $task->detail?->responsible_user_id;
        $entries = [];

        foreach ($task->detail?->collaborators ?? [] as $id) {
            $entries[$id] = ['به وظیفه ' . $title . ' اضافه شدید.', 'شما همکار این وظیفه هستید.'];
        }

        if ($accountableId) {
            $entries[$accountableId] = $this->accountableText($title);
        }

        if ($doerId) {
            $entries[$doerId] = ['وظیفه جدید: ' . $title, 'این وظیفه به شما ارجاع شد.'];
        }

        return $entries;
    }

    private function assignedEntries(Task $task, array $payload, string $title): array
    {
        $new = $payload['to'] ?? $task->user_id;
        $old = $payload['from'] ?? $task->user_id;

        if ((int)$new === (int)$old) {
            return [];
        }

        $entries = [];

        if ($old) {
            $entries[$old] = ['وظیفه از شما گرفته شد: ' . $title, 'این وظیفه به شخص دیگری ارجاع شد.'];
        }

        if ($new) {
            $entries[$new] = ['وظیفه جدید: ' . $title, 'این وظیفه به شما ارجاع شد.'];
        }

        return $entries;
    }

    private function helperEntries(array $payload, string $title): array
    {
        $entries = [];

        foreach ($payload['removed'] ?? [] as $id) {
            $entries[$id] = $this->removedText($title);
        }

        foreach ($payload['added'] ?? [] as $id) {
            $entries[$id] = ['به وظیفه ' . $title . ' اضافه شدید.', 'شما همکار این وظیفه هستید.'];
        }

        return $entries;
    }

    private function accountableEntries(array $payload, string $title): array
    {
        $entries = [];

        if ($from = $payload['from'] ?? null) {
            $entries[$from] = $this->removedText($title);
        }

        if ($to = $payload['to'] ?? null) {
            $entries[$to] = $this->accountableText($title);
        }

        return $entries;
    }

    private function broadcastEntries(string $kind, Task $task, array $payload, string $title): array
    {
        $text = match ($kind) {
            'status' => ['وضعیت تغییر کرد: ' . $title, 'به «' . trim(TaskStatus::tryFrom($payload['to'] ?? '')?->getLabel() ?? (string)($payload['to'] ?? '')) . '» منتقل شد.'],
            'completed' => ['وظیفه تکمیل شد: ' . $title, 'این وظیفه به پایان رسید.'],
            'approved' => ['تأیید شد: ' . $title, 'این وظیفه توسط مدیر تأیید شد.'],
            'sent_back' => ['برگشت خورد: ' . $title, 'این وظیفه برای بازبینی برگردانده شد.'],
            'reopened' => ['بازگشایی شد: ' . $title, 'این وظیفه دوباره باز شد.'],
            default => ['پاسخ جدید: ' . $title, 'پاسخ جدیدی برای این وظیفه ثبت شد.'],
        };

        $repliers = Reply::query()
            ->where('repliable_type', Task::class)
            ->where('repliable_id', $task->id)
            ->where(fn($q) => $q->whereNull('type')->orWhereIn('type', ['comment', 'attachment']))
            ->distinct()
            ->pluck('user_id')
            ->all();

        $ids = array_unique(array_filter([
            $task->assigned_to ?? $task->user_id,
            $task->user_id,
            $task->detail?->responsible_user_id,
            ...($task->detail?->collaborators ?? []),
            ...$repliers,
        ]));

        return array_fill_keys($ids, $text);
    }

    private function accountableText(string $title): array
    {
        return ['جوابگوی وظیفه ' . $title . ' شدید.', 'شما مسئول پاسخگویی این وظیفه هستید.'];
    }

    private function removedText(string $title): array
    {
        return ['از وظیفه ' . $title . ' حذف شدید.', 'دیگر در این وظیفه نقشی ندارید.'];
    }
}
