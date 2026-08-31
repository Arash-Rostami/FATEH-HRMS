<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    public function __construct(private ProjectSettings $settings)
    {
    }

    public function execute(ProjectForm $form): Project
    {
        $form->validate();

        return $this->create($form->name, $form->memberIds, $form->departments, $this->settings->bag($form));
    }

    public function create(string $name, array $memberIds = [], array $departments = [], array $settings = []): Project
    {
        return DB::transaction(fn() => Project::create([
            'name' => trim($name),
            'slug' => Project::generateSlug($name),
            'owner_id' => auth()->id(),
            'member_ids' => $this->filterExistingUserIds($memberIds),
            'departments' => array_values(array_unique($departments)),
            'settings' => $settings ?: null,
        ]));
    }

    public function resolvePendingProject(TaskForm $form): ?int
    {
        if (!$form->pendingProjectName || trim($form->pendingProjectName) === '') {
            return null;
        }

        return $this->create($form->pendingProjectName, $form->pendingProjectMemberIds, $form->pendingProjectDepartments)->id;
    }

    private function filterExistingUserIds(array $ids): array
    {
        return User::whereIn('id', array_unique($ids))->pluck('id')->all();
    }
}
