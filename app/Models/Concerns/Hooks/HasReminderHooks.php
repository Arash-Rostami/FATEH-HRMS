<?php

namespace App\Models\Concerns\Hooks;

trait HasReminderHooks
{
    protected static function bootHasReminderHooks(): void
    {
        static::saved(self::forgetOpenAndQueueDueOnSaved(...));
        static::deleted(self::forgetOpenByUser(...));
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
