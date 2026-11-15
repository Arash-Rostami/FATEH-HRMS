<?php

namespace App\Models\Concerns\Hooks;

trait HasReminderHooks
{
    protected static function bootHasReminderHooks(): void
    {
        static::saving(self::rearmOnDueChangeOnSaving(...));
        static::saved(self::forgetOpenAndQueueDueOnSaved(...));
        static::deleted(self::forgetOpenByUser(...));
    }

    private static function rearmOnDueChangeOnSaving(self $reminder)
    {
        if ($reminder->exists && $reminder->isDirty('due_at') && !$reminder->isDirty('notified_at')) {
            $reminder->notified_at = null;
        }
    }

    private static function forgetOpenByUser()
    {
        return static::$openByUser = [];
    }

    private static function forgetOpenAndQueueDueOnSaved(self $reminder)
    {
        self::forgetOpenByUser();
        $reminder->queueDueReconcile();
    }
}
