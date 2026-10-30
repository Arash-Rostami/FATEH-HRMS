<?php

namespace App\Models\Concerns\Hooks;

use App\Services\Notify\NotifyService;

trait HasNotifyRuleHooks
{
    protected static function bootHasNotifyRuleHooks(): void
    {
        static::saved(self::flushRulesOnChange(...));
        static::deleted(self::flushRulesOnChange(...));
    }

    private static function flushRulesOnChange(self $r)
    {
        return $r->isOneOff() || NotifyService::flush();
    }
}
