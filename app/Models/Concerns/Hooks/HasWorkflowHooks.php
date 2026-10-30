<?php

namespace App\Models\Concerns\Hooks;

use App\Services\ProjectTask\HandleWorkflowTransition;

trait HasWorkflowHooks
{
    protected static function bootHasWorkflowHooks(): void
    {
        static::updated(self::runTransitionOnUpdated(...));
    }

    private static function runTransitionOnUpdated(self $wf)
    {
        if (!$wf->wasChanged(['current_step', 'status'])) return;
        app(HandleWorkflowTransition::class)->execute($wf);
    }
}
