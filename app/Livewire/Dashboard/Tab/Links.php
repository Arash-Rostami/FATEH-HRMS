<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Presentation\LinkPresenter;
use App\Models\Link;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Isolate]
class Links extends Component
{
    use FocusOnRecord;

    public string $search = '';
    public string $activeFilter = 'all';

    #[Locked]
    public string $view = 'rail';

    #[Computed]
    public function externalLinks()
    {
        if ($this->open) {
            return $this->externalLinksSource->where('id', $this->open)->values();
        }

        if ($this->activeFilter === 'internal') {
            return collect();
        }

        return $this->filterBySearch($this->externalLinksSource);
    }

    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.external')]
    public function externalLinksSource()
    {
        return Link::external()->orderBy('sequence')->get();
    }

    #[Computed]
    public function internalLinks()
    {
        if ($this->open) {
            return $this->internalLinksSource->where('id', $this->open)->values();
        }

        if ($this->activeFilter === 'external') {
            return collect();
        }

        return $this->filterBySearch($this->internalLinksSource);
    }

    #[Computed(seconds: 7200, cache: true, key: 'dashboard.links.internal')]
    public function internalLinksSource()
    {
        return Link::internal()->orderBy('sequence')->get();
    }

    private function filterBySearch(Collection $links): Collection
    {
        if ($this->search === '') {
            return $links;
        }

        $needle = mb_strtolower(trim($this->search));

        return $links->filter(
            fn(Link $link) => str_contains(mb_strtolower($link->url_title ?? ''), $needle)
                || str_contains(mb_strtolower($link->url_description ?? ''), $needle)
        )->values();
    }

    public function setFilter(string $filter): void
    {
        if (!in_array($filter, ['all', 'internal', 'external'], true)) {
            return;
        }

        $this->open = null;
        $this->activeFilter = $filter;
    }

    public function mount(): void
    {
        $view = session('links_view_mode', 'rail');
        $this->view = in_array($view, ['rail', 'launch'], true) ? $view : 'rail';
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['rail', 'launch'], true)) {
            return;
        }

        $this->view = $view;
        session(['links_view_mode' => $view]);
    }

    public function updatedSearch(): void
    {
        $this->open = null;
    }

    public function resetFilters(): void
    {
        $this->open = null;
        $this->reset(['search', 'activeFilter']);
    }

    public function render()
    {
        return view('livewire.dashboard.tab.links', ['linkPresenter' => new LinkPresenter()]);
    }

    #[Computed]
    public function totalLinks()
    {
        return $this->externalLinksSource->count() + $this->internalLinksSource->count();
    }
}
