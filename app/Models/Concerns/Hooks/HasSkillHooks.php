<?php

namespace App\Models\Concerns\Hooks;

use App\Models\Skill;

trait HasSkillHooks
{
    protected static function bootHasSkillHooks(): void
    {
        static::deleting(self::blockDeleteWhenSelectedOnDeleting(...));
    }

    private static function blockDeleteWhenSelectedOnDeleting(Skill $skill)
    {
        if ($skill->skillUsers()->exists()) {
            throw new \RuntimeException('این مهارت توسط کاربران اتخاب شده و قابل حذف نیست.');
        }
    }
}
