<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use App\Traits\FocusOnRecord;

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
        if ($this->open && ! in_array($this->open, $this->photoIds ?? [], true)) {
            array_unshift($this->photoIds, $this->open);
            $this->selectedPhotoId = $this->open;
        }

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

        return Photo::whereIn('id', $this->photoIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.gallery');
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
            ->where(fn($q) => $q->whereNull('department_id')
                ->orWhere('department_id', '')
                ->orWhere('department_id', $dept)
            );
    }
}
