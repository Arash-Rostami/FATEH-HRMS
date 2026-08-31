<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait HasProjectMembers
{
    #[Computed]
    protected function projectMemberIds(): Collection
    {
        $project = $this->activeProject;
        if (!$project) {
            return collect();
        }

        return collect($project->member_ids ?? [])->push($project->owner_id)->filter()->unique();
    }

    #[Computed]
    protected function projectMemberUsers(): Collection
    {
        $ids = $this->projectMemberIds;
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $ids)->with('profile')->get();
    }

    #[Computed]
    public function mentionCandidates(): Collection
    {
        return $this->projectMemberUsers->where('status', 'active')->values();
    }
}
