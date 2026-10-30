<?php

namespace App\Models\Concerns\Hooks;

use App\Livewire\Dashboard\Project\Actions\CancelWorkflowAction;
use App\Models\User;
use App\Models\Workflow;
use App\Support\TaskAccessPolicy;
use Illuminate\Validation\ValidationException;

trait HasProjectHooks
{
    protected static function bootHasProjectHooks(): void
    {
        static::saving(self::normalizeSettingsAndOwnerOnSaving(...));
        static::saved(self::runFollowUpsOnSaved(...));
        static::deleting(self::cancelWorkflowsOnDeleting(...));
        static::restored(self::reviveWorkflowOnRestored(...));
    }

    private static function normalizeSettingsAndOwnerOnSaving(self $project)
    {
        if ($project->isDirty('settings') && is_array($project->settings)) {
            $project->settings = static::pruneSettings($project->settings) ?: null;
        }

        if ($project->isDirty('member_ids')) {
            $project->member_ids = TaskAccessPolicy::assignableIds($project->member_ids ?? []);
        }

        if (!$project->isDirty('owner_id')) return;

        if ($project->owner_id && !User::visibleOnBoard()->whereKey($project->owner_id)->exists()) {
            throw ValidationException::withMessages(['owner_id' => 'مالک جدید باید عضو فعال این پروژه باشد.']);
        }

        if ($previousOwner = $project->getOriginal('owner_id')) {
            $project->member_ids = array_values(array_unique([...($project->member_ids ?? []), $previousOwner]));
        }
    }

    private static function runFollowUpsOnSaved(self $project)
    {
        $project->charter = null;
        $project->syncChannelMembershipIfNeeded();
        $project->logCreationIfNeeded();
        $project->logMembershipChangesIfNeeded();
        $project->logSettingsChangesIfNeeded();
        $project->handleOwnerChangeIfNeeded();
        $project->releaseRemovedMembersIfNeeded();
        $project->approveDoneTasksIfApprovalDisabled();
        $project->syncTaskDetailProjectName();
    }

    private static function cancelWorkflowsOnDeleting(self $project)
    {
        if (!$project->isForceDeleting()) {
            $cancel = app(CancelWorkflowAction::class);

            Workflow::forProject($project->id)
                ->whereIn('status', [Workflow::STATUS_ACTIVE, Workflow::STATUS_DRAFT])
                ->get()
                ->each(fn (Workflow $wf) => $cancel->cancel($wf, 'project_deleted'));
        }
    }

    private static function reviveWorkflowOnRestored(self $project)
    {
        return app(CancelWorkflowAction::class)->revive($project);
    }
}
