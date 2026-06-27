<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Presentation\GalleryPresenter;
use App\Models\Photo;
use App\Traits\FocusOnRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Gallery extends Component
{
    use FocusOnRecord;

    #[Locked]
    public array $photoIds = [];

    public ?int $selectedPhotoId = null;

    public bool $assetsLoaded = false;
    public int $perPage = 5;
    public bool $hasMorePages = true;

    public function loadInitialPhotos(): void
    {
        $this->photoIds = $this->getBaseQuery()->take($this->perPage)->pluck('id')->toArray();

        $this->hasMorePages = count($this->photoIds) >= $this->perPage;

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

        $newIds = $this->getBaseQuery()
            ->skip(count($this->photoIds))
            ->take($this->perPage)
            ->pluck('id')
            ->toArray();

        if (empty($newIds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->photoIds = array_merge($this->photoIds, $newIds);
        unset($this->photos);
    }

    public function mount(): void
    {
        // FOCUS MODE: when arriving from the command palette with ?open={id},
        // show nothing but that single photo (respecting department visibility).
        if ($this->open && $this->getBaseQuery()->whereKey($this->open)->exists()) {
            $this->photoIds = [$this->open];
            $this->selectedPhotoId = $this->open;
            $this->hasMorePages = false;
            $this->assetsLoaded = true;
            return;
        }

        $this->open = null;
        $this->loadInitialPhotos();
        $this->assetsLoaded = true;
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
        $dept = Auth::user()->profile->department ?? null;

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
