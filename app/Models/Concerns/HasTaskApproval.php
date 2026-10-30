<?php

namespace App\Models\Concerns;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAccessPolicy;
use App\Support\TaskAccessPolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Validation\ValidationException;

trait HasTaskApproval
{
    public function isPendingApproval(): bool
    {
        return $this->status === 'done'
            && $this->approved_at === null
            && $this->approvalProject()?->setting('requires_approval') === true;
    }

    public function isFrozenFor(?User $user): bool
    {
        return $this->status === 'done'
            && $this->approved_at !== null
            && $this->approvalProject()?->setting('requires_approval') === true
            && !($this->project && ProjectAccessPolicy::canManageTasks($this->project, $user));
    }

    public function isGenuinelyDone(): bool
    {
        return $this->status === 'done' && $this->approved_at !== null;
    }

    protected function approvalProject(): ?Project
    {
        if (!$this->project_id) return null;

        $loaded = $this->relationLoaded('project') ? $this->project : null;

        if ($loaded && (int)$loaded->getKey() === (int)$this->project_id
            && array_diff(['settings', 'owner_id'], array_keys($loaded->getAttributes())) === []) {
            return $loaded;
        }

        if ($this->relationLoaded('project') && $this->project === null && !$this->isDirty('project_id')) return null;

        $project = Project::find($this->project_id, ['id', 'settings', 'owner_id', 'member_ids', 'departments']);
        $this->setRelation('project', $project);

        return $project;
    }

    protected function guardTicketLock(bool $deleting = false): void
    {
        if (!$this->ticket_id || static::$ticketSyncing) return;

        if (!$deleting && array_diff(array_keys($this->getDirty()), ['archived_at', 'updated_at']) === []) return;

        throw ValidationException::withMessages(['form' => 'این وظیفه از تیکت ساخته شده و فقط از طریق تیکت تغییر می‌کند.']);
    }

    protected function guardApprovedFreeze(bool $deleting = false): void
    {
        if (static::$ticketSyncing || !auth()->check()) return;

        if ($this->getOriginal('status') !== 'done' || $this->getOriginal('approved_at') === null) return;

        if (!$deleting && array_diff(array_keys($this->getDirty()), ['archived_at', 'rank', 'updated_at']) === []) return;

        $project = $this->isDirty('project_id')
            ? Project::find($this->getOriginal('project_id'), ['id', 'settings', 'owner_id'])
            : $this->approvalProject();

        if ($project?->setting('requires_approval') !== true || ProjectAccessPolicy::canManageTasks($project, auth()->user())) return;

        throw ValidationException::withMessages(['form' => 'این وظیفه تأیید شده و قفل است؛ فقط مالک پروژه می‌تواند آن را بازگشایی کند.']);
    }

    protected function guardPastDeadline(): void
    {
        if (static::$ticketSyncing || !$this->deadline || !$this->isDirty('deadline') || !auth()->check()) return;

        $original = $this->getOriginal('deadline');

        if ((!$original || !$this->deadline->isSameDay($original)) && $this->deadline->isBefore(today())) {
            throw ValidationException::withMessages(['deadline' => __('resources/task/strings.validation.deadline_past')]);
        }
    }

    protected function clearArchiveOnStatusChange(): void
    {
        $reopened = $this->getOriginal('status') === 'done';

        if ($this->status !== 'done' && ($reopened || $this->archived_at !== null)) {
            $this->archived_at = null;
            $this->completed_at = null;

            if ($reopened) {
                $this->approved_at = null;
                $this->approved_by = null;
            }
        }
    }

    protected function reevaluateApprovalOnMove(): void
    {
        if ($this->status !== 'done' || $this->approved_at !== null || $this->isDirty('status')) {
            return;
        }

        $target = $this->project_id ? Project::find($this->project_id, ['id', 'settings']) : null;

        if (!$target?->charter()->requiresApproval()) {
            $this->approved_at = now();
            $this->approved_by = auth()->id();
        }
    }

    protected function handleApprovalOnCompletion(): void
    {
        if ($this->status !== 'done' || $this->getOriginal('status') === 'done') {
            return;
        }

        $this->completed_at ??= now();
        $project = $this->approvalProject();
        $actor = auth()->id();

        if ($project?->setting('requires_approval') !== true) {
            if ($this->approved_at === null) {
                $this->approved_at = now();
                $this->approved_by = $actor;
            }

            return;
        }

        if (ProjectAccessPolicy::canManageTasks($project, auth()->user())) {
            $this->approved_at = now();
            $this->approved_by = $actor;
        } else {
            $this->approved_at = null;
            $this->approved_by = null;
        }
    }

    protected function canChangeStatus(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskAccessPolicy::canChangeStatus($this, auth()->user())
        );
    }

    protected function isFrozen(): Attribute
    {
        return Attribute::make(get: fn() => $this->isFrozenFor(auth()->user()));
    }

    protected function canReopen(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'done'
                && $this->approved_at !== null
                && $this->archived_at === null
                && $this->approvalProject()?->setting('requires_approval') === true
                && ProjectAccessPolicy::canManageTasks($this->project, auth()->user())
        );
    }

    protected function canDelete(): Attribute
    {
        return Attribute::make(
            get: fn() => TaskAccessPolicy::canDelete($this, auth()->user())
        );
    }
}