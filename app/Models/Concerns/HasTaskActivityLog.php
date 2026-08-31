<?php

namespace App\Models\Concerns;

use App\Jobs\SyncTaskCalendarEventJob;
use App\Models\Task;
use App\Services\ProjectTask\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait HasTaskActivityLog
{
    public static function bootHasTaskActivityLog(): void
    {
        static::saved(function (Model $model) {
            $triggers = $model->calendarTriggers ?? [];

            $calendarRelevant = $model->wasRecentlyCreated
                ? (bool)array_filter($triggers, fn($field) => !empty($model->{$field}))
                : (bool)array_intersect(array_keys($model->getChanges()), $triggers);

            if ($calendarRelevant) {
                SyncTaskCalendarEventJob::dispatch($model instanceof Task ? $model->id : $model->task_id);
            }

            if ($model->wasRecentlyCreated) return;

            $activityMap = $model->activityMap ?? [];
            $hasMappedChange = (bool)array_intersect(array_keys($model->getChanges()), array_keys($activityMap));

            if (!$hasMappedChange) return;

            $task = $model instanceof Task ? $model : $model->task;

            if ($task) {
                app(ActivityLogger::class)->logMappedChanges($task, $model, auth()->user(), $activityMap);
            }
        });
    }
}
