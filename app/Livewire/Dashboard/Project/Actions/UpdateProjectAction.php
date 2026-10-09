<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Models\Project;
use App\Services\ProjectTask\ProjectSettings;
use App\Models\User;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateProjectAction
{
    public function __construct(private ProjectSettings $settings)
    {
    }

    public function execute(Project $project, ProjectForm $form): void
    {
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);

        $form->validate();

        DB::transaction(function () use ($project, $form) {
            $this->guardNotStale($project, $form);

            $project->fill([
                'name' => trim($form->name),
                'member_ids' => $form->memberIds,
                'departments' => array_values(array_unique($form->departments)),
                'settings' => $this->settings->bag($form, $project->settings['deadline'] ?? null) ?: null,
            ]);

            $this->applyOwner($project, $form->ownerId);

            $project->save();
        });
    }

    private function applyOwner(Project $project, ?int $ownerId): void
    {
        if (!$ownerId || $ownerId === $project->owner_id) return;

        $candidate = User::visibleOnBoard()->with('profile:user_id,department_id')->find($ownerId);

        if (!$candidate || !$project->isVisibleTo($candidate)) {
            throw ValidationException::withMessages([
                'projectForm.ownerId' => 'مالک جدید باید عضو فعال این پروژه باشد.',
            ]);
        }

        $project->owner_id = $ownerId;
    }

    private function guardNotStale(Project $project, ProjectForm $form): void
    {
        if ($form->updatedAt === null) return;

        $current = Project::query()->lockForUpdate()->find($project->id);

        if ($current && $current->updated_at->timestamp !== $form->updatedAt) {
            throw ValidationException::withMessages([
                'form' => 'این پروژه توسط شخص دیگری تغییر کرده؛ لطفاً صفحه را بازخوانی کرده و دوباره تلاش کنید.',
            ]);
        }
    }
}
