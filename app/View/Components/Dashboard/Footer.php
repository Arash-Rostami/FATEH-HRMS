<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    public function __construct(
        public bool  = false
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.dashboard.footer');
    }
}
