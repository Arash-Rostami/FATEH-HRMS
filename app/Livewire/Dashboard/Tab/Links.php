<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Links extends Component
{
    #[Computed]
    public function internalLinks()
    {
        return Cache::remember('dashboard.links.internal', 7200, function () {
            return Link::internal()->orderBy('sequence')->get();
        });
    }

    #[Computed]
    public function externalLinks()
    {
        return Cache::remember('dashboard.links.external', 7200, function () {
            return Link::external()->orderBy('sequence')->get();
        });
    }

    public function render()
    {
        return view('livewire.dashboard.tab.links.index');
    }
}
