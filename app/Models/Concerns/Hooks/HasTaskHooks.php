<?php

namespace App\Models\Concerns\Hooks;

use App\Livewire\Dashboard\Project\Actions\AdvanceWorkflowStepAction;
use App\Models\Project;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Services\ProjectTask\EventSyncService;
use App\Support\TaskAccessPolicy;

trait HasTaskHooks
{
    protected static function bootHasTaskHooks(): void
    {
        static::forceDeleting(self::cleanUpOnForceDeleting(...));
        static::deleting(self::guardAndPurgeEventOnDeleting(...));
        static::restoring(self::guardTicketLockOnRestoring(...));
        static::restored(self::syncEventOnRestored(...));
        static::creating(self::handleApprovalOnCreating(...));
        static::created(self::logCreationOnCreated(...));
        static::saving(self::assertAssigneeAndDefaultDeadlineOnSaving(...));
        static::updating(self::guardAndRerankOnUpdating(...));
        static::saved(self::handleProjectMoveOnSaved(...));
        static::deleted(self::detachWorkflowStepOnDeleted(...));
        static::updated(self::syncCycleAssigneesOnUpdated(...));
        static::updated(self::completeWorkflowStepOnUpdated(...));
    }

    private static function cleanUpOnForceDeleting(self $task)
    {
        if ($task->detail) {
            static::deleteStoredFiles($task->detail->attachments);
        }

        $task->replies->each->delete();

        app(EventSyncService::class)->purge($task);
    }

    private static function guardAndPurgeEventOnDeleting(self $task)
    {
        $task->guardTicketLock(true);
        $task->guardApprovedFreeze(true);

        if (!$task->isForceDeleting()) {
            app(EventSyncService::class)->purge($task);
        }
    }

    private static function guardTicketLockOnRestoring(self $task)
    {
        return $task->guardTicketLock(true);
    }

    private static function syncEventOnRestored(self $task)
    {
        app(EventSyncService::class)->sync($task);
    }

    private static function handleApprovalOnCreating(self $task)
    {
        if ($task->status !== 'done') return;

        if ($task->approved_at === null) {
            $task->handleApprovalOnCompletion();
        } else {
            $task->completed_at ??= now();
        }
    }

    private static function logCreationOnCreated(self $task)
    {
        app(ActivityLogger::class)->taskCreated($task, $task->ticket_id ? $task->creator : (auth()->user() ?? $task->creator));
    }

    private static function assertAssigneeAndDefaultDeadlineOnSaving(self $task)
    {
        if (static::$ticketSyncing) return;

        $assigning = $task->isDirty('assigned_to') && filled($task->assigned_to);
        $defaulting = blank($task->deadline) && $task->project_id && (!$task->exists || $task->isDirty(['deadline', 'project_id']));
        $project = ($assigning || $defaulting) && $task->project_id
            ? ($task->hasRosterProject() ? $task->project : Project::find($task->project_id, ['id', 'settings', 'owner_id', 'member_ids', 'departments']))
            : null;

        if ($assigning) {
            TaskAccessPolicy::assertAssignable([$task->assigned_to], $project);
        }

        if ($defaulting) {
            $task->deadline = $project?->charter()->defaultDeadline();
        }
    }

    private static function guardAndRerankOnUpdating(self $task)
    {
        $task->guardTicketLock();
        $task->guardApprovedFreeze();

        if ($task->isDirty('status')) {
            $task->clearArchiveOnStatusChange();
            $task->handleApprovalOnCompletion();
        }

        if ($task->isDirty('project_id')) {
            $task->reevaluateApprovalOnMove();
        }

        $task->guardPastDeadline();
        $task->rerankIfMoved();
    }

    private static function handleProjectMoveOnSaved(self $task)
    {
        if ($task->wasRecentlyCreated || !$task->wasChanged('project_id')) return;

        static::$movedProjects[$task->id] = true;

        if ($task->project_id === null) {
            try {
                $task->detail?->update(['project' => null]);
            } finally {
                unset(static::$movedProjects[$task->id]);
            }
        }

        if ($oldProjectId = $task->getOriginal('project_id')) {
            app(AdvanceWorkflowStepAction::class)->detachStep($task, $oldProjectId);
        }
    }

    private static function detachWorkflowStepOnDeleted(self $task)
    {
        return app(AdvanceWorkflowStepAction::class)->detachStep($task);
    }

    private static function syncCycleAssigneesOnUpdated(self $task)
    {
        if ($task->project_id && $task->wasChanged('assigned_to')) {
            app(CycleLinkResolver::class)->syncAssignees($task, $task->relationLoaded('detail') ? ($task->detail?->collaborators ?? []) : null);
        }
    }

    private static function completeWorkflowStepOnUpdated(self $task)
    {
        if (!$task->project_id || $task->status !== 'done' || $task->approved_at === null) return;
        if (!$task->wasChanged('status') && !$task->wasChanged('approved_at')) return;
        app(AdvanceWorkflowStepAction::class)->completeForTask($task);
    }
}
