<?php

namespace App\Models\Concerns;

use App\Enums\TaskActivityType;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProjectTask\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasProjectFollowUps
{
    protected function logCreationIfNeeded(): void
    {
        if (!$this->wasRecentlyCreated || !$this->owner_id) {
            return;
        }

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, [
            'created' => true,
            'owner_name' => auth()->id() === $this->owner_id ? auth()->user()->name : User::whereKey($this->owner_id)->value('name'),
        ]);
    }

    protected function syncTaskDetailProjectName(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('name')) {
            return;
        }

        TaskDetail::whereIn('task_id', Task::withTrashed()->where('project_id', $this->id)->select('id'))->update(['project' => $this->name]);
        ModelCacheVersion::bump(TaskDetail::class);
    }

    protected function approveDoneTasksIfApprovalDisabled(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('settings')) {
            return;
        }

        if (($this->getOriginal('settings')['requires_approval'] ?? null) !== true || $this->setting('requires_approval') === true) {
            return;
        }

        $this->tasks()->where('status', 'done')->whereNull('approved_at')->get()->each(function (Task $task) {
            $task->systemApproval = true;
            $task->update(['approved_at' => now(), 'approved_by' => null]);
        });
    }

    protected function logSettingsChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('settings')) {
            return;
        }

        $before = $this->getOriginal('settings') ?? [];
        $after = $this->settings ?? [];

        $changed = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        if ($changed === []) {
            return;
        }

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, ['changed' => $changed]);
    }

    protected function logMembershipChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged(['member_ids', 'departments'])) {
            return;
        }

        $payload = [];

        if ($this->wasChanged('member_ids')) {
            $before = $this->getOriginal('member_ids') ?? [];
            $after = $this->member_ids ?? [];
            $previousOwner = $this->wasChanged('owner_id') ? [$this->getOriginal('owner_id')] : [];
            $payload['added'] = array_values(array_diff($after, $before, $previousOwner));
            $payload['removed'] = array_values(array_diff($before, $after));
        }

        if ($this->wasChanged('departments')) {
            $before = $this->getOriginal('departments') ?? [];
            $after = $this->departments ?? [];
            $payload['added_departments'] = array_values(array_diff($after, $before));
            $payload['removed_departments'] = array_values(array_diff($before, $after));
        }

        if (array_filter($payload) === []) {
            return;
        }

        app(ActivityLogger::class)->system(
            $this,
            auth()->user(),
            TaskActivityType::Assignment,
            $payload
        );
    }

    public function releaseUsers(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        DB::transaction(function () use ($userIds) {
            $this->tasks()
                ->whereIn('assigned_to', $userIds)
                ->where('status', '!=', 'done')
                ->whereNull('archived_at')
                ->get()
                ->each->update(['assigned_to' => null]);

            static::stripTaskPeople($this->tasks(), $userIds);
            static::stripWorkflowAssignees($this->workflows(), $userIds);
        });
    }

    public static function stripTaskPeople($tasks, array $userIds): void
    {
        TaskDetail::whereIn('task_id', $tasks->where('status', '!=', 'done')->whereNull('archived_at')->select('id'))
            ->where(function (Builder $query) use ($userIds) {
                $query->whereIn('responsible_user_id', $userIds);

                foreach ($userIds as $id) {
                    $query->orWhereJsonContains('collaborators', $id);
                }
            })
            ->get()
            ->each(fn(TaskDetail $detail) => $detail->update([
                'collaborators' => array_values(array_diff($detail->collaborators ?? [], $userIds)),
                'responsible_user_id' => in_array($detail->responsible_user_id, $userIds) ? null : $detail->responsible_user_id,
            ]));
    }

    public static function stripWorkflowAssignees($workflows, array $userIds): void
    {
        $workflows->whereIn('status', [Workflow::STATUS_ACTIVE, Workflow::STATUS_DRAFT])->get()
            ->each(function (Workflow $workflow) use ($userIds) {
                $changed = false;

                $steps = array_map(function (array $step) use ($userIds, &$changed) {
                    $current = $step['assignee_user_ids'] ?? [];
                    $kept = array_values(array_diff($current, $userIds));

                    if ($kept === array_values($current)) {
                        return $step;
                    }

                    $changed = true;

                    return [...$step, 'assignee_user_ids' => $kept];
                }, $workflow->steps ?? []);

                if ($changed) {
                    $workflow->update(['steps' => $steps]);
                }
            });
    }

    public function ensureActiveOwner(): void
    {
        if ($this->owner_id && User::active()->whereKey($this->owner_id)->exists()) {
            return;
        }

        $successor = $this->charter()->rosterQuery()
            ->with('profile:user_id,position')
            ->whereIn('id', $this->member_ids ?? [])
            ->get()
            ->sortBy(fn(User $user) => [$user->rank(), $user->id])
            ->first();

        if ($successor) {
            $this->update(['owner_id' => $successor->id]);
        }
    }

    protected function handleOwnerChangeIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('owner_id')) {
            return;
        }

        DB::transaction(fn() => $this->workflows()
            ->whereNotIn('status', [Workflow::STATUS_COMPLETED, Workflow::STATUS_CANCELLED])
            ->get()
            ->each->update(['owner_id' => $this->owner_id]));

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, [
            'owner_id' => $this->owner_id,
            'owner_name' => User::whereKey($this->owner_id)->value('name'),
        ]);
    }

    protected function releaseRemovedMembersIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('member_ids')) {
            return;
        }

        $removed = array_diff($this->getOriginal('member_ids') ?? [], $this->member_ids ?? [], [$this->owner_id]);

        if ($removed === []) {
            return;
        }

        $this->releaseUsers(
            User::with('profile:user_id,department_id')
                ->whereIn('id', $removed)
                ->get()
                ->reject(fn(User $user) => $this->isVisibleTo($user))
                ->pluck('id')
                ->all()
        );
    }

    protected function syncChannelMembershipIfNeeded(): void
    {
        $membershipRelevant = $this->wasRecentlyCreated
            || $this->wasChanged(['member_ids', 'departments', 'owner_id']);

        if ($membershipRelevant) {
            SyncProjectChannelMembershipJob::dispatch($this->id);
        }
    }
}