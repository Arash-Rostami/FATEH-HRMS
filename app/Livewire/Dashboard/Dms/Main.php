<?php

namespace App\Livewire\Dashboard\Dms;

use App\Livewire\Dashboard\Dms\Actions\ConfirmReadAction;
use App\Models\DMS;
use App\Models\Read;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Main extends Component
{
    public string $search = '';
    public ?string $activeFilter = 'all';
    public int $perPage = 10;
    public bool $hasMorePages = true;

    #[Locked]
    public array $docIds = [];

    public function confirmRead(int $docId, ConfirmReadAction $action): void
    {
        $action->execute($docId);
        unset($this->confirmedDocs, $this->readDocs);
    }

    #[Computed]
    public function confirmedDocs()
    {
        return Read::where('user_id', auth()->id())
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

        return DMS::with('reads')
            ->whereIn('id', $this->docIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function getAuthorizedFile(string $filename): Response
    {
        $doc = DMS::visibleToUser()->where('file', $filename)->first();
        if (!$doc) return response()->view('errors.document-not-found', [], 404);


        $filePath = Storage::disk('public')->path($filename);
        if (!file_exists($filePath) || !is_file($filePath)) {
            return response()->view('errors.document-not-found', [], 404);
        }

        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf'
            ? response()->file($filePath)
            : response()->download($filePath);
    }

    public function incrementRead(int $docId, ConfirmReadAction $action): void
    {
        $action->execute($docId, increment: true);
        unset($this->confirmedDocs, $this->readDocs);
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

    public function mount(): void
    {
        $this->loadInitialDocs();
    }

    #[On('confirmation-confirmed')]
    public function onConfirmationConfirmed(string $method, int $params, ConfirmReadAction $action): void
    {
        $action->execute($params);
        unset($this->confirmedDocs, $this->readDocs);
    }

    #[Computed]
    public function readDocs()
    {
        return Read::where('user_id', auth()->id())
            ->where('read', true)
            ->where('read_count', '>', 0)
            ->pluck('document_id')
            ->unique()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.dms')
            ->extends('layouts.app')
            ->section('content');
    }

    #[Computed]
    public function totalDocs()
    {
        return DMS::visibleToUser()->count();
    }

    #[Computed]
    public function types()
    {
        return DMS::visibleToUser()
            ->get()
            ->filter(fn($item) => isset($item->extra['Type']) || isset($item->extra['type']))
            ->map(fn($item) => $item->extra['Type'] ?? $item->extra['type'])
            ->filter()
            ->unique()
            ->values();
    }

    public function updatedActiveFilter(): void { $this->loadInitialDocs(); }

    public function updatedSearch(): void { $this->loadInitialDocs(); }

    private function getBaseQuery(): Builder
    {
        return DMS::query()
            ->visibleToUser()
            ->when($this->search, fn($query, $search) => $query->where(fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('version', 'like', "%{$search}%")
                ->orWhereJsonContains('extra->category', $search)
                ->orWhereJsonContains('extra->Category', $search)
                ->orWhereJsonContains('extra->type', $search)
                ->orWhereJsonContains('extra->Type', $search)
            )
            )
            ->when($this->activeFilter !== 'all', fn($query) => $query->where(fn($q) => $q->whereJsonContains('extra->type', $this->activeFilter)
                ->orWhereJsonContains('extra->Type', $this->activeFilter)
            )
            )->latest();
    }
}
