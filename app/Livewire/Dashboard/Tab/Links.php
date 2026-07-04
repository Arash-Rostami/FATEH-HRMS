<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Link;
use App\Traits\FocusOnRecord;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Links extends Component
{
    use FocusOnRecord;


    #[Computed]
    public function externalLinks()
    {
        $links = $this->externalLinksSource;

        return $this->open
            ? $links->where('id', $this->open)->values()
            : $links;
    }

    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.external')]
    public function externalLinksSource()
    {
        return Link::external()->orderBy('sequence')->get();
    }

    #[Computed]
    public function internalLinks()
    {
        $links = $this->internalLinksSource;

        return $this->open
            ? $links->where('id', $this->open)->values()
            : $links;
    }

    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.internal')]
    public function internalLinksSource()
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
        return $this->externalLinksSource->count() + $this->internalLinksSource->count();
    }
}
