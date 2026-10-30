<?php

namespace App\Models\Concerns\Hooks;

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
        static::saved(self::reconcileFreeAssignmentOnSaved(...));
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
