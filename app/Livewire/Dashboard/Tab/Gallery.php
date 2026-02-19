<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Photo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Gallery extends Component
{
    #[Locked]
    public array $photoIds = [];

    public ?int $selectedPhotoId = null;

    public bool $assetsLoaded = false;
    public int $perPage = 5; // Adjusted for gallery
    public bool $hasMorePages = true;

    #[Computed]
    public function photos()
    {
        if (empty($this->photoIds)) return collect();

        $idsString = implode(',', $this->photoIds);

        // Assuming 'path' is cast to array in model, no extra relationship needed unless user/comments added later
        return Photo::whereIn('id', $this->photoIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function loadInitialPhotos()
    {
        $user = Auth::user();
        $profile = $user?->profile;
        // Use getRawOriginal because 'department' is both a column and a relationship
        $dept = $profile?->getRawOriginal('department');

        $query = Photo::orderBy('event_date', 'desc')
            ->where(fn($q) => $q->whereNull('department')
                ->orWhere('department', '')
                ->orWhere('department', $dept));

        // Get initial batch of IDs
        $this->photoIds = $query->take($this->perPage)->pluck('id')->toArray();

        // Check if there are more
        $totalFound = count($this->photoIds);
        $this->hasMorePages = $totalFound >= $this->perPage;

        if (!empty($this->photoIds) && !$this->selectedPhotoId) {
            $this->selectedPhotoId = $this->photoIds[0];
        }

        unset($this->photos); // Clear computed cache
    }

    public function loadMore()
    {
        if (!$this->hasMorePages) return;

        $user = Auth::user();
        $profile = $user?->profile;
        $dept = $profile?->getRawOriginal('department');

        $newIds = Photo::orderBy('event_date', 'desc')
            ->where(fn($q) => $q->whereNull('department')
                ->orWhere('department', '')
                ->orWhere('department', $dept))
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

    public function mount()
    {
        $this->loadInitialPhotos();
        $this->assetsLoaded = true;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.gallery.index');
    }
}
