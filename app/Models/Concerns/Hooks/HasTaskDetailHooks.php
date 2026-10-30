<?php

namespace App\Models\Concerns\Hooks;

use App\Models\Project;
use App\Models\Task;
use App\Services\Menu\StateService;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait HasTaskDetailHooks
{
    protected static function bootHasTaskDetailHooks(): void
    {
        static::saved(self::flushMenuStateOnSaved(...));
        static::creating(self::guardDuplicateDetailOnCreating(...));
        static::saving(self::assertAssigneesOnSaving(...));
        static::saving(self::fillOrgAndProjectOnSaving(...));
        static::saved(self::syncCycleAssigneesOnSaved(...));
    }

    private static function flushMenuStateOnSaved(self $detail)
    {
        if ($detail->wasChanged('state')) {
            DB::afterCommit(fn() => StateService::flush());
        }
    }

    private static function guardDuplicateDetailOnCreating(self $detail)
    {
        if (static::where('task_id', $detail->task_id)->exists()) {
            throw new RuntimeException(__('resources/task/strings.validation.duplicate_detail'));
        }
    }

    private static function assertAssigneesOnSaving(self $detail)
    {
        $moved = Task::pullProjectMove($detail->task_id);

        if (!$moved && !$detail->isDirty(['collaborators', 'responsible_user_id'])) return;

        $people = array_filter([...($detail->collaborators ?? []), $detail->responsible_user_id]);

        if (!$moved && $detail->exists) {
            $people = array_diff($people, $detail->getOriginal('collaborators') ?? [], [$detail->getOriginal('responsible_user_id')]);
        }

        if ($people === []) return;

        $projectId = Task::withTrashed()->whereKey($detail->task_id)->value('project_id');

        TaskAccessPolicy::assertAssignable($people, $projectId ? Project::find($projectId) : null);
    }

    private static function fillOrgAndProjectOnSaving(self $detail)
    {
        $needsOrg = (!$detail->exists || ($detail->relationLoaded('task') && $detail->task->wasChanged('assigned_to')))
            && (blank($detail->department_id) || blank($detail->unit) || blank($detail->section));

        if (!$needsOrg && !empty($detail->project)) return;

        $task = $detail->task ?? Task::find($detail->task_id);

        if ($needsOrg && $task?->assigned_to) {
            $detail->fillOrgFromProfile($task);
        }

        if (empty($detail->project) && $task?->project_id) {
            $detail->project = Project::find($task->project_id)?->name;
        }
    }

    private static function syncCycleAssigneesOnSaved(self $detail)
    {
        if ($detail->wasRecentlyCreated || !$detail->wasChanged('collaborators')) return;

        $task = Task::find($detail->task_id, ['id', 'project_id', 'assigned_to']);

        if ($task?->project_id) {
            app(CycleLinkResolver::class)->syncAssignees($task, $detail->collaborators ?? []);
        }
    }
}
