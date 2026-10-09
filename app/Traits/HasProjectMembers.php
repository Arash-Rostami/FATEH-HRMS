<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait HasProjectMembers
{
    #[Computed]
    protected function projectMemberIds(): Collection
    {
        return $this->projectMemberUsers->pluck('id');
    }

    #[Computed]
    protected function projectMemberUsers(): Collection
    {
        return $this->activeProject?->charter()->rosterQuery()->with('profile')->get() ?? collect();
    }

    #[Computed]
    public function mentionCandidates(): Collection
    {
        return $this->projectMemberUsers->where('status', 'active')->values();
    }
}
