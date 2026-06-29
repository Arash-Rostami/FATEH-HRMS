<?php

namespace App\Models\Traits;

use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

trait HasMenuState
{
    public static function bootHasMenuState(): void
    {
        $events = defined('static::MENU_STATE_EVENTS')
            ? static::MENU_STATE_EVENTS
            : ['created', 'updated', 'deleted'];

        $flush = fn() => DB::afterCommit(fn() => StateService::flush());

        foreach ($events as $event) {
            static::{$event}($flush);
        }
    }
}
