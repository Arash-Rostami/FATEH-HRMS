<?php

namespace App\Models\Concerns;

use App\Models\Reminder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReminders
{
    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable')->orderBy('due_at');
    }

    protected static function bootHasReminders(): void
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->reminders->each->delete();
        });
    }
}
