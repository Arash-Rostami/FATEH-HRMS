<?php

namespace App\Livewire\Dashboard\Dms;

use App\Livewire\Dashboard\Dms\Actions\ConfirmReadAction;
use App\Livewire\Dashboard\Dms\Presentation\DmsPresenter;
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

    private const SORT_COLUMNS = [
        'updated' => 'updated_at',
        'title' => 'title',
        'code' => 'code',
    ];

    private ?array $parsedActiveFilterCache = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'systematic';
    public string $search = '';
    public ?string $activeFilter = 'all';
    public string $sort = 'updated';
    public string $sortDir = 'desc';
    public ?string $pendingFilter = null;
    public int $perPage = 10;
    public bool $hasMorePages = true;
    #[Locked]
    public array $docIds = [];

    public function confirmRead(int $docId, ConfirmReadAction $action): void
    {
        $action->execute($docId);
        $this->refreshReadState();
    }

    #[Computed]
    public function confirmedDocs(): array
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
        if (empty($this->docIds)) {
            return collect();
        }

        $safeIds = array_map('intval', $this->docIds);
        $idsString = implode(',', $safeIds);

        return DMS::query()
            ->whereIn('id', $safeIds)
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

        $this->visibleTabQuery()
            ->get()
            ->each(function ($item) use (&$groups) {
                foreach (['type', 'Type'] as $k) {
                    foreach ((array)($item->extra[$k] ?? []) as $v) {
                        if ($v) {
                            $groups['type'][] = $v;
                        }
                    }
                }
                foreach (($item->tags ?? []) as $key => $vals) {
                    $group = strtolower($key);
                    foreach ((array)$vals as $v) {
                        if ($v) {
                            $groups[$group][] = $v;
                        }
                    }
                }
            });

        return array_map(fn($v) => array_values(array_unique($v)), $groups);
    }

    public function getAuthorizedFile(string $filename): Response
    {
        $doc = DMS::visibleToUser()->where('file', $filename)->first();

        if (!$doc) {
            return $this->notFound();
        }

        return $this->serveFile(Storage::disk('public')->path($doc->file));
    }

    public function getAuthorizedExtraFile(string $filename): Response
    {
        $doc = DMS::visibleToUser()->whereJsonContains('extra_files', $filename)->first();

        if (!$doc) {
            return $this->notFound();
        }

        $disk = Storage::disk('public');
        $filePath = $disk->path($filename);
        $realPath = realpath($filePath);
        $diskRoot = realpath($disk->path(''));

        if ($realPath === false || $diskRoot === false || $realPath === $diskRoot || !str_starts_with($realPath, $diskRoot . DIRECTORY_SEPARATOR)) {
            return $this->notFound();
        }

        return $this->serveFile($filePath);
    }

    public function incrementRead(int $docId, ConfirmReadAction $action): void
    {
        $action->execute($docId, increment: true);
        $this->refreshReadState();
    }

    public function sortBy(string $column): void
    {
        if (!array_key_exists($column, self::SORT_COLUMNS)) {
            return;
        }

        if ($this->sort === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->sortDir = 'asc';
        }

        $this->resetAndReload();
    }

    public function togglePendingFilter(string $which): void
    {
        if (!in_array($which, ['receive', 'read'], true)) {
            return;
        }

        $this->pendingFilter = $this->pendingFilter === $which ? null : $which;
        $this->resetAndReload();
    }

    public function clearPendingFilter(): void
    {
        if ($this->pendingFilter === null) {
            return;
        }

        $this->pendingFilter = null;
        $this->resetAndReload();
    }

    public function resetSort(): void
    {
        if ($this->sort === 'updated' && $this->sortDir === 'desc') {
            return;
        }

        $this->sort = 'updated';
        $this->sortDir = 'desc';
        $this->resetAndReload();
    }

    public function loadInitialDocs(): void
    {
        $ids = $this->matchingDocIds();
        $this->docIds = array_slice($ids, 0, $this->perPage);
        $this->hasMorePages = count($ids) > $this->perPage;
        unset($this->docs);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) {
            return;
        }

        $ids = $this->matchingDocIds();
        $newIds = array_slice($ids, count($this->docIds), $this->perPage);

        if (empty($newIds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->docIds = array_merge($this->docIds, $newIds);
        unset($this->docs);
    }

    public function mount(): void
    {
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
        $this->refreshReadState();
    }

    #[Computed]
    public function readDocs(): array
    {
        return Read::where('user_id', auth()->id())
            ->where('read', true)
            ->where('read_count', '>', 0)
            ->pluck('document_id')
            ->unique()
            ->toArray();
    }

    #[Computed]
    public function readPendingCount(): int
    {
        return $this->visibleTabQuery()
            ->whereIn('id', $this->confirmedDocs)
            ->whereNotIn('id', $this->readDocs)
            ->count();
    }

    #[Computed]
    public function receivePendingCount(): int
    {
        return $this->visibleTabQuery()
            ->whereNotIn('id', $this->confirmedDocs)
            ->count();
    }

    public function render()
    {
        return view('livewire.dashboard.dms')
            ->extends('layouts.app')
            ->section('content');
    }

    #[Computed]
    public function presenter(): DmsPresenter
    {
        return new DmsPresenter();
    }

    public function restoreAfterFocus(): void
    {
        $this->hasMorePages = true;
        $this->loadInitialDocs();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search = "";
        $this->activeFilter = "all";
        $this->pendingFilter = null;
        $this->resetAndReload();
    }

    #[Computed]
    public function totalDocs(): int
    {
        return $this->visibleTabQuery()->count();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetAndReload();
    }

    public function updatedSearch(): void
    {
        $this->resetAndReload();
    }

    protected function recordFocusType(): string
    {
        return 'dms';
    }

    private function applyTabFilter(Builder $query): Builder
    {
        return $this->activeTab === 'systematic' ? $query->systematic() : $query->nonSystematic();
    }

    private function visibleTabQuery(): Builder
    {
        return $this->applyTabFilter(DMS::visibleToUser());
    }

    private function parsedActiveFilter(): array
    {
        return $this->parsedActiveFilterCache ??= (function (): array {
            $parts = explode('|', $this->activeFilter, 2);

            return [$parts[0] ?? '', $parts[1] ?? ''];
        })();
    }

    private function getBaseQuery(): Builder
    {
        [$sortCol, $sortDir] = $this->validSort();

        return $this->visibleTabQuery()
            ->when($this->search !== '', function ($query) {
                $needle = $this->search;
                $escapedNeedle = trim(json_encode($needle), '"');

                return $query->where(fn($q) => $q
                    ->whereRaw('INSTR(title, ?) > 0', [$needle])
                    ->orWhereRaw('INSTR(code, ?) > 0', [$needle])
                    ->orWhereRaw('INSTR(version, ?) > 0', [$needle])
                    ->orWhereRaw('INSTR(revision, ?) > 0', [$needle])
                    ->orWhereJsonContains('extra->category', $needle)
                    ->orWhereJsonContains('extra->Category', $needle)
                    ->orWhereJsonContains('extra->type', $needle)
                    ->orWhereJsonContains('extra->Type', $needle)
                    ->orWhereRaw('INSTR(CAST(extra AS CHAR), ?) > 0', [$needle])
                    ->orWhereRaw('INSTR(CAST(tags AS CHAR), ?) > 0', [$needle])
                    ->orWhereRaw('INSTR(CAST(extra AS CHAR), ?) > 0', [$escapedNeedle])
                    ->orWhereRaw('INSTR(CAST(tags AS CHAR), ?) > 0', [$escapedNeedle])
                );
            })
            ->when(
                $this->activeFilter !== 'all' && $this->parsedActiveFilter()[0] === 'type',
                function ($query) {
                    [, $value] = $this->parsedActiveFilter();

                    $query->where(fn($q) => $q
                        ->whereJsonContains('extra->type', $value)
                        ->orWhereJsonContains('extra->Type', $value)
                        ->whereJsonContains('tags->type', $value, 'or')
                        ->whereJsonContains('tags->Type', $value, 'or')
                    );
                }
            )
            ->when($this->pendingFilter === 'receive', fn($query) => $query->whereNotIn('id', $this->confirmedDocs))
            ->when($this->pendingFilter === 'read', fn($query) => $query->whereIn('id', $this->confirmedDocs)->whereNotIn('id', $this->readDocs))
            ->orderByRaw($this->readPriorityExpression(), [auth()->id()])
            ->orderBy($sortCol, $sortDir)
            ->orderBy('id', 'desc');
    }

    private function readPriorityExpression(): string
    {
        return "COALESCE(
            (SELECT CASE WHEN read_count = 0 THEN 1 ELSE 2 END
                FROM `reads`
                WHERE `reads`.document_id = dms.id AND `reads`.user_id = ?
                LIMIT 1),
            0
        ) ASC";
    }

    private function validSort(): array
    {
        $col = self::SORT_COLUMNS[$this->sort] ?? 'updated_at';
        $dir = strtolower($this->sortDir) === 'asc' ? 'asc' : 'desc';

        return [$col, $dir];
    }

    private function matchingDocIds(): array
    {
        $query = $this->getBaseQuery();

        if ($this->activeFilter === 'all' || $this->parsedActiveFilter()[0] === 'type') {
            return $query->pluck('id')->all();
        }

        [$key, $value] = $this->parsedActiveFilter();
        $variants = array_unique([$key, ucfirst($key)]);

        $ids = [];

        foreach ($query->select(['id', 'tags'])->cursor() as $doc) {
            if (self::tagMatches($doc->tags, $variants, $value)) {
                $ids[] = $doc->id;
            }
        }

        return $ids;
    }

    private static function tagMatches(?array $tags, array $variants, string $value): bool
    {
        foreach ($variants as $variant) {
            $tagValue = $tags[$variant] ?? null;

            if ($tagValue === null) {
                continue;
            }

            if (is_array($tagValue) ? in_array($value, $tagValue, true) : $tagValue === $value) {
                return true;
            }
        }

        return false;
    }

    private function notFound(): Response
    {
        return response()->view('errors.document-not-found', [], 404);
    }

    private function refreshReadState(): void
    {
        unset($this->confirmedDocs, $this->readDocs);
    }

    private function resetAndReload(): void
    {
        $this->open = null;
        $this->loadInitialDocs();
    }

    private function serveFile(string $filePath): Response
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return $this->notFound();
        }

        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf'
            ? response()->file($filePath)
            : response()->download($filePath);
    }
}
