<?php

namespace App\Services\Menu\Notifications;

use App\Enums\TaskActivityType;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Reply;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Menu\Contracts\MenuNudge;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class ProjectNudge implements MenuNudge
{
    private array $plan = [];

    public function __construct(private readonly string $kind = 'invite')
    {
    }

    public function getKey(): string
    {
        return $this->kind === 'step' ? 'workflow:nudge' : 'projects-controller:nudge';
    }

    public function triggers(): array
    {
        if ($this->kind === 'step') {
            return [
                ['class' => Workflow::class, 'on' => ['deleted'], 'subject' => null],
                ['class' => Reply::class, 'on' => ['created'], 'subject' => fn(Reply $reply) =>
                    $reply->repliable_type === Project::class
                    && $reply->type === TaskActivityType::WorkflowStep
                    && ($id = $reply->payload['workflow'] ?? null)
                        ? Workflow::find($id)
                        : null],
            ];
        }

        return [
            ['class' => Project::class, 'on' => ['created', 'deleted', 'forceDeleted'], 'subject' => null],
            ['class' => Reply::class, 'on' => ['created'], 'subject' => fn(Reply $reply) =>
                $reply->repliable_type === Project::class && $this->isInviteEvent($reply) ? $reply->repliable : null],
        ];
    }

    public function for($subject): Collection
    {
        $this->plan = $this->kind === 'step' ? $this->stepPlan($subject) : $this->invitePlan($subject);

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
        return $this->kind === 'step'
            ? route('projects', ['open' => $subject->project_id, 'tab' => 'workflow'])
            : route('projects', ['open' => $subject->getKey()]);
    }

    private function isInviteEvent(Reply $reply): bool
    {
        return match ($reply->type) {
            TaskActivityType::Assignment => true,
            TaskActivityType::SettingsChange => isset($reply->payload['owner_id']),
            default => false,
        };
    }

    private function projectReplies(int $projectId, TaskActivityType $type)
    {
        return Reply::query()
            ->where('repliable_type', Project::class)
            ->where('repliable_id', $projectId)
            ->where('type', $type);
    }

    private function holders(string $itemId): array
    {
        return DatabaseNotification::query()
            ->where('type', FilamentDatabaseNotification::class)
            ->where('notifiable_type', User::class)
            ->where('data->menu_key', $this->getKey())
            ->where('data->item_id', $itemId)
            ->pluck('notifiable_id')
            ->map(fn($id) => (int)$id)
            ->all();
    }

    private function invitePlan(Project $project): array
    {
        $ownerId = (int)$project->owner_id;
        $roster = array_flip($project->charter()->rosterIds());
        $added = [];
        $actors = [];
        $codes = [];

        $recent = $this->projectReplies($project->id, TaskActivityType::Assignment)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->get(['user_id', 'payload']);

        foreach ($recent as $reply) {
            $actors[] = (int)$reply->user_id;
            $codes = [...$codes, ...($reply->payload['added_departments'] ?? [])];

            foreach ($reply->payload['added'] ?? [] as $id) {
                if ((int)$id !== (int)$reply->user_id) {
                    $added[] = (int)$id;
                }
            }
        }

        if ($codes !== []) {
            $added = [...$added, ...array_diff(Profile::whereIn('department_id', $codes)->pluck('user_id')->map(fn($id) => (int)$id)->all(), $actors)];
        }

        if ($project->created_at?->gt(now()->subMinutes(10))) {
            $added = [...$added, ...array_keys($roster)];
        }

        $ownerName = $ownerId ? User::whereKey($ownerId)->value('name') : null;
        $plan = [];

        foreach (array_unique([...$added, ...$this->holders((string)$project->id)]) as $id) {
            if ($id !== $ownerId && isset($roster[$id])) {
                $plan[$id] = [
                    "{$ownerName} شما را به پروژه «{$project->name}» دعوت کرد.",
                    'برای مشاهده پروژه کلیک کنید.',
                ];
            }
        }

        $change = $this->projectReplies($project->id, TaskActivityType::SettingsChange)
            ->whereNotNull('payload->owner_id')
            ->orderByDesc('id')
            ->first(['user_id', 'payload']);

        if ($change && (int)($change->payload['owner_id'] ?? 0) === $ownerId && (int)$change->user_id !== $ownerId) {
            $plan[$ownerId] = ["مالک پروژه «{$project->name}» شدید.", 'اکنون مدیریت این پروژه با شماست.'];
        }

        return $plan;
    }

    private function stepPlan(Workflow $workflow): array
    {
        $steps = $workflow->steps ?? [];
        $people = fn(?int $index) => $index === null ? [] : array_map('intval', $steps[$index]['assignee_user_ids'] ?? []);
        $label = fn(?int $index) => $index === null ? '' : ($steps[$index]['label'] ?? '');
        $context = "چرخهٔ «{$workflow->name}»";
        $current = $workflow->isActive() ? $workflow->current_step : null;

        $event = $this->projectReplies((int)$workflow->project_id, TaskActivityType::WorkflowStep)
            ->where('payload->workflow', $workflow->id)
            ->orderByDesc('id')
            ->first(['user_id', 'payload']);

        $payload = $event?->payload ?? [];
        $turn = ['نوبت شماست: ' . $label($current), $context];
        $plan = [];

        foreach (array_intersect($this->holders((string)$workflow->id), $people($current)) as $id) {
            $plan[$id] = $turn;
        }

        switch ($payload['event'] ?? null) {
            case 'started':
            case 'advanced':
                foreach ($people($current) as $id) {
                    $plan[$id] = $turn;
                }

                if ($payload['forced'] ?? false) {
                    foreach (array_diff($people($payload['step'] ?? null), $people($current)) as $id) {
                        $plan[$id] = ['مرحلهٔ «' . $label($payload['step']) . '» توسط مدیر پروژه رد شد.', $context];
                    }
                }
                break;
            case 'rejected':
                foreach ($people($current) as $id) {
                    $plan[$id] = ['مرحله برگشت خورد: ' . $label($current), $payload['note'] ?? $context];
                }
                break;
            case 'reassigned':
                foreach ($payload['added'] ?? [] as $id) {
                    if (in_array((int)$id, $people($current), true)) {
                        $plan[(int)$id] = $turn;
                    }
                }

                foreach ($payload['removed'] ?? [] as $id) {
                    $plan[(int)$id] = ['از مرحلهٔ «' . $label($payload['step'] ?? null) . '» حذف شدید.', $context];
                }
                break;
            case 'completed':
                if ($workflow->owner_id) {
                    $plan[(int)$workflow->owner_id] = ["چرخهٔ «{$workflow->name}» تکمیل شد.", $context];
                }
                break;
            case 'cancelled':
                if (($payload['reason'] ?? null) !== 'project_deleted') {
                    foreach ($people($workflow->current_step) as $id) {
                        $plan[$id] = ["چرخهٔ «{$workflow->name}» لغو شد.", $context];
                    }
                }
                break;
        }

        unset($plan[(int)$event?->user_id]);

        return $plan;
    }
}
