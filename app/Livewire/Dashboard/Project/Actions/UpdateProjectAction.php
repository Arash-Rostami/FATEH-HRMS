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

            $project->update([
                'name' => trim($form->name),
                'member_ids' => $this->filterExistingUserIds($form->memberIds),
                'departments' => array_values(array_unique($form->departments)),
                'settings' => $this->settings->bag($form) ?: null,
            ]);
        });
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

    private function filterExistingUserIds(array $ids): array
    {
        return User::whereIn('id', array_unique($ids))->pluck('id')->all();
    }
}
