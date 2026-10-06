<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Vite;
use Illuminate\View\View;

class ModuleAssetChunksComposer
{
    private const MODULES = ['dms', 'ths', 'taskboard', 'project', 'channel', 'contact', 'reservation', 'profile', 'analytics', 'energy', 'tasksheet'];

    public function compose(View $view): void
    {
        $chunks = [];

        foreach (self::MODULES as $module) {
            try {
                $chunks[$module] = Vite::asset("resources/js/components/alpine/modules/{$module}.js");
            } catch (\Throwable $e) {
            }
        }

        $view->with('chunks', $chunks);
    }
}
