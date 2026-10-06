<?php

namespace App\View\Composers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class ErrorTraceComposer
{
    public function compose(View $view): void
    {
        $view->with('trace_id', 'TRC-' . strtoupper(Str::random(8)));
    }
}
