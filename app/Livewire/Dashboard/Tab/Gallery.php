<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Presentation\GalleryPresenter;
use App\Models\Photo;
use App\Traits\FocusOnRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Isolate]
class Gallery extends Component
{
    use FocusOnRecord;

    #[Locked]
    public array $photoIds = [];

    #[Locked]
    public string $view = 'filmstrip';

    public ?int $selectedPhotoId = null;

    public int $perPage = 5;
    public bool $hasMorePages = true;

    public function loadInitialPhotos(): void
    {
        $ids = $this->getBaseQuery()->take($this->perPage + 1)->pluck('id')->toArray();

        $this->hasMorePages = count($ids) > $this->perPage;
        $this->photoIds = array_slice($ids, 0, $this->perPage);

        if (!empty($this->photoIds) && !$this->selectedPhotoId) {
            $this->selectedPhotoId = $this->photoIds[0];
        }

        unset($this->photos);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) {
            return;
        }

        $ids = $this->getBaseQuery()
            ->skip(count($this->photoIds))
            ->take($this->perPage + 1)
            ->pluck('id')
            ->toArray();

        $this->hasMorePages = count($ids) > $this->perPage;

        $newIds = array_slice($ids, 0, $this->perPage);

        if (empty($newIds)) {
            return;
        }

        $this->photoIds = array_merge($this->photoIds, $newIds);
        unset($this->photos);
    }

    public function mount(): void
    {
        $view = session('gallery_view_mode', 'filmstrip');
        $this->view = in_array($view, ['filmstrip', 'wall'], true) ? $view : 'filmstrip';

        // FOCUS MODE: when arriving from the command palette with ?open={id},
        // show nothing but that single photo (respecting department visibility).
        if ($this->open && $this->getBaseQuery()->whereKey($this->open)->exists()) {
            $this->view = 'filmstrip';
            $this->photoIds = [$this->open];
            $this->selectedPhotoId = $this->open;
            $this->hasMorePages = false;
            return;
        }

        $this->open = null;
        $this->loadInitialPhotos();
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['filmstrip', 'wall'], true)) {
            return;
        }

        $this->view = $view;
        session(['gallery_view_mode' => $view]);
    }

    #[Computed]
    public function photos()
    {
        if (empty($this->photoIds)) {
            return collect();
        }

        $idsString = implode(',', array_map('intval', $this->photoIds));

        return Photo::with('department')
            ->whereIn('id', $this->photoIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.gallery', ['presenter' => new GalleryPresenter()]);
    }

    /** Called by FocusOnRecord::clearFocus() when the user taps "show all". */
    public function restoreAfterFocus(): void
    {
        $this->hasMorePages = true;
        $this->loadInitialPhotos();
    }

    #[Computed]
    public function totalPhotos()
    {
        return $this->getBaseQuery()->count();
    }

    private function getBaseQuery(): Builder
    {
        $dept = Auth::user()?->profile?->department_id;

        return Photo::query()
            ->orderByDesc('event_date')
            ->where(function($q) use ($dept) {
                if ($dept) {
                    $q->where('department_id', $dept)
                        ->orWhereJsonContains('departments', $dept);
                }

                $q->orWhere(function ($p) {
                    $p->where(function ($x) {
                        $x->whereNull('department_id')->orWhere('department_id', '');
                    })->where(function ($y) {
                        $y->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0');
                    });
                });
            });
    }
}
