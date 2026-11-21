<?php

namespace App\Models\Concerns\Hooks;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait HasDepartmentHooks
{
    protected static function bootHasDepartmentHooks(): void
    {
        static::saving(self::normalizeLevelAndGuardCycleOnSaving(...));
        static::saving(self::guardFreeAssignmentOnSaving(...));
        static::saving(self::lockCodeOnSaving(...));
        static::deleting(self::blockDeleteWhileReferencedOnDeleting(...));
        static::saved(self::reconcileFreeAssignmentOnSaved(...));
    }

    private static function lockCodeOnSaving(self $department)
    {
        if ($department->exists && $department->isDirty('code')) {
            throw ValidationException::withMessages(['data.code' => __('resources/department/strings.errors.code_locked')]);
        }
    }

    private static function blockDeleteWhileReferencedOnDeleting(self $department)
    {
        $referenced = collect(['profiles', 'authorities', 'faqs', 'photos', 'reports', 'media', 'reviews', 'task_details'])
            ->contains(fn(string $table) => DB::table($table)->where('department_id', $department->code)->exists())
            || collect(['feeds', 'photos', 'posts', 'reports', 'media', 'suggestions'])
                ->contains(fn(string $table) => DB::table($table)->whereJsonContains('departments', $department->code)->exists())
            || Project::withTrashed()->whereJsonContains('departments', $department->code)->exists()
            || Ticket::query()->where('extra->target_department', $department->code)->exists()
            || DB::table('dms')->whereJsonContains('owners', $department->code)->exists()
            || DB::table('notify_rules')->whereJsonContains('extra->departments', $department->code)->exists()
            || static::query()->where('subordinate_to', $department->code)->exists();

        if ($referenced) {
            throw ValidationException::withMessages(['department' => __('resources/department/strings.errors.in_use')]);
        }
    }

    private static function normalizeLevelAndGuardCycleOnSaving(self $department)
    {
        if ($department->subordinate_to !== null && (int) $department->level === 0) {
            $department->level = 1;
        }

        if (static::wouldCreateCycle($department->code, $department->subordinate_to)) {
            throw new RuntimeException(__('resources/department/strings.errors.cyclic_hierarchy'));
        }
    }

    private static function guardFreeAssignmentOnSaving(self $department)
    {
        if (
            $department->isDirty('free_assignment')
            && !$department->free_assignment
            && (bool) $department->getOriginal('free_assignment')
            && static::flipOffBlocked($department->code)
        ) {
            throw ValidationException::withMessages(['data.free_assignment' => __('resources/department/strings.errors.free_assignment_blocked')]);
        }
    }

    private static function reconcileFreeAssignmentOnSaved(self $department)
    {
        if ($department->wasChanged('free_assignment')) {
            DB::afterCommit(fn () => static::afterFreeAssignmentChange($department->code));
        }
    }
}
