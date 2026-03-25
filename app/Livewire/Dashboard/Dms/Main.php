<?php

namespace App\Livewire\Dashboard\Dms;

use App\Models\DMS as DMSModel;
use App\Models\Read;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Main extends Component
{
    public string $search = '';

    public ?string $activeFilter = 'all';

    #[Locked]
    public array $docIds = [];

    public int $perPage = 10;
    public bool $hasMorePages = true;

    public function confirmRead($docId)
    {
        $this->confirmOrIncrementRead($docId);
    }

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
    public function docs()
    {
        if (empty($this->docIds)) return collect();

        $idsString = implode(',', $this->docIds);

        return DMSModel::with('reads')
            ->whereIn('id', $this->docIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function getAuthorizedFile($filename)
    {
        $filePath = storage_path("app/public/dms/" . $filename);

        if (file_exists($filePath) && is_file($filePath)) return response()->file($filePath);

        return response()->view('errors.document-not-found', [], 404);
    }

    public function incrementRead($docId)
    {
        $this->confirmOrIncrementRead($docId, true);
    }

    public function loadInitialDocs(): void
    {
        $this->docIds = $this->getBaseQuery()->take($this->perPage)->pluck('id')->toArray();
        $this->hasMorePages = count($this->docIds) >= $this->perPage;

        unset($this->docs);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $newIds = $this->getBaseQuery()
            ->skip(count($this->docIds))
            ->take($this->perPage)
            ->pluck('id')
            ->toArray();

        if (empty($newIds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->docIds = array_merge($this->docIds, $newIds);
        unset($this->docs);
    }

    public function mount()
    {
        $this->loadInitialDocs();
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

    public function render()
    {
        return view('livewire.dashboard.dms.index')
            ->extends('layouts.app')
            ->section('content');
    }

    #[Computed]
    public function totalDocs()
    {
        return DMSModel::visibleToUser()->count();
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

    public function updatedActiveFilter()
    {
        $this->loadInitialDocs();
    }

    public function updatedSearch()
    {
        $this->loadInitialDocs();
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

            unset($this->confirmedDocs);
            unset($this->readDocs);
        }
    }

    private function getBaseQuery(): Builder
    {
        return DMSModel::query()
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
            })->latest();
    }
}
