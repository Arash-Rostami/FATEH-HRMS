<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Link;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Links extends Component
{
    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.external')]
    public function externalLinks()
    {
        return Link::external()->orderBy('sequence')->get();
    }

    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.internal')]
    public function internalLinks()
    {
        return Link::internal()->orderBy('sequence')->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.links');
    }

    #[Computed]
    public function totalLinks()
    {
        return Link::internal()->count() + Link::external()->count();
    }
}
