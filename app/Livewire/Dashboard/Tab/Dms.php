<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\DMS as DMSModel;
use App\Models\Read;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Dms extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $activeFilter = 'all';

    public int $perPage = 10;

    #[Computed]
    public function confirmedDocs()
    {
        return Read::query()
            ->where('user_id', auth()->id())
            ->where('read', true)
            ->pluck('document_id')
            ->unique()
            ->toArray();
    }

    #[Computed]
    public function readDocs()
    {
        return Read::query()
            ->where('user_id', auth()->id())
            ->where('read', true)
            ->where('read_count', '>', 0)
            ->pluck('document_id')
            ->unique()
            ->toArray();
    }

    public function confirmRead($docId)
    {
        $this->confirmOrIncrementRead($docId);
    }

    public function incrementRead($docId)
    {
        $this->confirmOrIncrementRead($docId, true);
    }

    protected function confirmOrIncrementRead($docId, $increment = false)
    {
        $document = DMSModel::find($docId);

        if ($document) {
            $readRecord = $document->reads()->firstOrCreate(
                ['user_id' => auth()->id()],
                ['read_count' => 0, 'read' => true]
            );

            if ($increment) {
                $readRecord->increment('read_count');
                $document->increment('combined_read_count');
            }

            // Unset computed properties to force refresh on next access
            unset($this->confirmedDocs);
            unset($this->readDocs);
        }
    }

    #[Computed]
    public function docs()
    {
        return DMSModel::query()
            ->with('reads')
            ->visibleToUser()
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('version', 'like', '%' . $search . '%')
                        ->orWhereJsonContains('extra->category', $search)
                        ->orWhereJsonContains('extra->Category', $search)
                        ->orWhereJsonContains('extra->type', $search)
                        ->orWhereJsonContains('extra->Type', $search);
                });
            })
            ->when($this->activeFilter !== 'all', function ($query) {
                $query->where(function ($q) {
                     $q->whereJsonContains('extra->type', $this->activeFilter)
                       ->orWhereJsonContains('extra->Type', $this->activeFilter);
                });
            })
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function types()
    {
        return DMSModel::visibleToUser()
            ->get()
            ->filter(fn($item) => isset($item->extra['Type']) || isset($item->extra['type']))
            ->map(fn($item) => ($item->extra['Type'] ?? $item->extra['type']))
            ->filter()
            ->unique()
            ->values();
    }

    #[Computed]
    public function totalDocs()
    {
        return DMSModel::visibleToUser()->count();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.dms.index');
    }
}
