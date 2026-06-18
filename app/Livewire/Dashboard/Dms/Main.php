<?php

namespace App\Livewire\Dashboard\Dms;

use App\Livewire\Dashboard\Dms\Actions\ConfirmReadAction;
use App\Models\DMS;
use App\Models\Read;
use App\Traits\FocusOnRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Main extends Component
{
    use FocusOnRecord;

    private const FILTER_LABELS = ['type' => 'دسته بندی'];


    #[Url(as: 'tab')]
    public string $activeTab = 'systematic';
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

    public function filterGroupLabel(string $key): string
    {
        return self::FILTER_LABELS[$key] ?? $key;
    }

    #[Computed]
    public function filterGroups(): array
    {
        $groups = [];

        DMS::visibleToUser()->get()->each(function ($item) use (&$groups) {
            foreach (['type', 'Type'] as $k) {
                foreach ((array)($item->extra[$k] ?? []) as $v) {
                    if ($v) $groups['type'][] = $v;
                }
            }
            foreach (($item->tags ?? []) as $key => $vals) {
                $group = strtolower($key) === 'type' ? 'type' : strtolower($key);
                foreach ((array)$vals as $v) {
                    if ($v) $groups[$group][] = $v;
                }
            }
        });

        return array_map(fn($v) => array_values(array_unique($v)), $groups);
    }

    public function getAuthorizedFile(string $filename): Response
    {
        $doc = DMS::visibleToUser()->where('file', basename($filename))->first();
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
        // FOCUS MODE: when arriving from the command palette with ?open={id},
        // show nothing but that single document (if the user is allowed to see it).
        if ($this->open && DMS::visibleToUser()->whereKey($this->open)->exists()) {
            $this->docIds = [$this->open];
            $this->hasMorePages = false;
            return;
        }

        $this->open = null;
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

    #[Computed]
    public function readPendingCount()
    {
        return DMS::visibleToUser()->whereIn('id', $this->confirmedDocs)->whereNotIn('id', $this->readDocs)->count();
    }

    #[Computed]
    public function receivePendingCount()
    {
        return DMS::visibleToUser()->whereNotIn('id', $this->confirmedDocs)->count();
    }

    public function render()
    {
        return view('livewire.dashboard.dms')
            ->extends('layouts.app')
            ->section('content');
    }

//    #[Computed]
//    public function types()
//    {
//        return DMS::visibleToUser()
//            ->get()
//            ->flatMap(function ($item) {
//                $types = [];
//                if (isset($item->extra['Type'])) $types[] = $item->extra['Type'];
//                if (isset($item->extra['type'])) $types[] = $item->extra['type'];
//                if (!empty($item->tags) && is_array($item->tags)) {
//                    $types = array_merge($types, $item->tags);
//                }
//                return $types;
//            })
//            ->filter()
//            ->unique()
//            ->values();
//    }

    /** Called by FocusOnRecord::clearFocus() when the user taps "show all". */
    public function restoreAfterFocus(): void
    {
        $this->hasMorePages = true;
        $this->loadInitialDocs();
    }

    #[Computed]
    public function totalDocs()
    {
        return DMS::visibleToUser()
            ->when($this->activeTab === 'systematic', fn($query) => $query->systematic(), fn($query) => $query->nonSystematic())
            ->count();
    }

    public function updatedActiveFilter(): void
    {
        $this->open = null;
        $this->loadInitialDocs();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search = "";
        $this->activeFilter = "all";
        $this->open = null;
        $this->loadInitialDocs();
    }

    public function updatedSearch(): void
    {
        $this->open = null;
        $this->loadInitialDocs();
    }

    protected function recordFocusType(): string { return 'dms'; }


    private function getBaseQuery(): Builder
    {
        return DMS::query()
            ->visibleToUser()
            ->when($this->activeTab === 'systematic', fn($query) => $query->systematic(), fn($query) => $query->nonSystematic())
            ->when($this->search, fn($query, $search) => $query->where(fn($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('version', 'like', "%{$search}%")
                ->orWhereJsonContains('extra->category', $search)
                ->orWhereJsonContains('extra->Category', $search)
                ->orWhereJsonContains('extra->type', $search)
                ->orWhereJsonContains('extra->Type', $search)
            ))
            ->when($this->activeFilter !== 'all', function ($query) {
                [$key, $value] = explode('|', $this->activeFilter, 2) + ['', ''];
                $key === 'type'
                    ? $query->where(fn($q) => $q
                    ->whereJsonContains('extra->type', $value)
                    ->orWhereJsonContains('extra->Type', $value)
                    ->orWhereJsonContains('tags->type', $value)
                    ->orWhereJsonContains('tags->Type', $value)
                )
                    : $query->where(fn($q) => $q->whereJsonContains("tags->{$key}", $value)
                    ->orWhereJsonContains("tags->" . ucfirst($key), $value)
                );
            })
            ->latest();
    }
}
