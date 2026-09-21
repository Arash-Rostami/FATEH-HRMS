<?php

namespace App\Livewire\Dashboard\Project;

use App\Livewire\Dashboard\Project\Actions\AdvanceWorkflowStepAction;
use App\Livewire\Dashboard\Project\Actions\ApplyTemplateAction;
use App\Livewire\Dashboard\Project\Actions\CancelWorkflowAction;
use App\Livewire\Dashboard\Project\Actions\ReassignWorkflowStepAction;
use App\Livewire\Dashboard\Project\Actions\SaveAsTemplateAction;
use App\Livewire\Dashboard\Project\Actions\SaveWorkflowAction;
use App\Livewire\Dashboard\Project\Actions\StartWorkflowAction;
use App\Livewire\Dashboard\Project\Presentation\WorkflowPresenter;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow as WorkflowModel;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Defer]
class Workflow extends Component
{
    #[Locked]
    public ?int $activeProjectId = null;

    public bool $isEditorOpen = false;
    public ?int $editingWorkflowId = null;
    public string $workflowName = '';
    public array $steps = [];

    public ?int $reassigningWorkflowId = null;
    public array $reassignSelection = [];
    public array $stepNotes = [];

    public string $selectedTemplateId = '';

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;
        $this->steps = [$this->blankStep()];
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.workflow.placeholder');
    }

    #[Computed]
    public function project(): ?Project
    {
        return $this->activeProjectId ? Project::visibleTo(auth()->user())->find($this->activeProjectId) : null;
    }

    #[Computed]
    public function isOwner(): bool
    {
        return $this->project && ProjectAccessPolicy::canManageAudience($this->project, auth()->user());
    }

    #[Computed]
    public function activeWorkflows(): Collection
    {
        if (!$this->activeProjectId) {
            return collect();
        }

        return WorkflowModel::forProject($this->activeProjectId)
            ->whereIn('status', [WorkflowModel::STATUS_DRAFT, WorkflowModel::STATUS_ACTIVE])
            ->latest()
            ->get();
    }

    #[Computed]
    public function historyWorkflows(): Collection
    {
        if (!$this->activeProjectId) {
            return collect();
        }

        return WorkflowModel::forProject($this->activeProjectId)
            ->whereIn('status', [WorkflowModel::STATUS_COMPLETED, WorkflowModel::STATUS_CANCELLED])
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'completed_at']);
    }

    #[Computed]
    public function templates(): Collection
    {
        return WorkflowModel::templates()->get(['id', 'name']);
    }

    #[Computed]
    public function memberCandidates(): array
    {
        return User::getCachedActiveOptions()
            ->except(auth()->id())
            ->map(fn ($name, $id) => ['id' => (int) $id, 'name' => $name])
            ->values()
            ->all();
    }

    #[Computed]
    public function batchedTasks(): Collection
    {
        $taskIds = $this->activeWorkflows
            ->pluck('steps')
            ->collapse()
            ->filter(fn (array $step) => ($step['populate_task'] ?? false) && !empty($step['task_id']))
            ->pluck('task_id')
            ->unique()
            ->values();

        if ($taskIds->isEmpty()) {
            return collect();
        }

        return Task::withTrashed()
            ->whereIn('id', $taskIds)
            ->with(['assignee:id,name', 'detail:id,task_id,state'])
            ->get(['id', 'title', 'status', 'assigned_to', 'deadline', 'approved_at', 'deleted_at'])
            ->keyBy('id');
    }

    #[Computed]
    public function batchedUserNames(): array
    {
        $ids = $this->activeWorkflows
            ->pluck('step_log')
            ->filter()
            ->collapse()
            ->pluck('completed_by')
            ->filter()
            ->values()
            ->all();

        return WorkflowModel::resolveUserNames($ids);
    }

    private function blankStep(): array
    {
        return [
            'label' => '',
            'type' => 'action',
            'assignee_user_ids' => [],
            'reject_to_step' => null,
            'populate_task' => false,
            'task_title' => null,
            'task_description' => null,
            'deadline_days' => null,
        ];
    }

    private function resetEditor(): void
    {
        $this->editingWorkflowId = null;
        $this->workflowName = '';
        $this->steps = [$this->blankStep()];
    }

    private function findWorkflow(int $id): ?WorkflowModel
    {
        return WorkflowModel::forProject($this->activeProjectId)->find($id);
    }

    private function firstError(ValidationException $e): string
    {
        return collect($e->errors())->flatten()->first() ?? 'خطا در اعتبارسنجی.';
    }

    private function normalizedSteps(): array
    {
        return collect($this->steps)->map(function (array $step) {
            $step['reject_to_step'] = $step['reject_to_step'] === '' ? null : $step['reject_to_step'];
            $step['deadline_days'] = $step['deadline_days'] === '' ? null : $step['deadline_days'];

            return $step;
        })->all();
    }

    public function openNewWorkflow(): void
    {
        $this->resetEditor();
        $this->isEditorOpen = true;
    }

    public function openEditWorkflow(int $workflowId): void
    {
        if (!$this->isOwner) {
            return;
        }

        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow || !$workflow->isDraft()) {
            return;
        }

        $this->editingWorkflowId = $workflow->id;
        $this->workflowName = $workflow->name;
        $this->steps = $workflow->steps;
        $this->isEditorOpen = true;
    }

    public function closeEditor(): void
    {
        $this->resetEditor();
        $this->isEditorOpen = false;
    }

    public function updated(string $name): void
    {
        if (!preg_match('/^steps\.(\d+)\.type$/', $name, $m)) {
            return;
        }

        $index = (int) $m[1];
        if (($this->steps[$index]['type'] ?? 'action') === 'approval') {
            $this->steps[$index]['populate_task'] = false;
            $this->steps[$index]['task_title'] = null;
            $this->steps[$index]['task_description'] = null;
            $this->steps[$index]['deadline_days'] = null;
        }
    }

    public function addStep(): void
    {
        $this->steps[] = $this->blankStep();
    }

    public function removeStep(int $index): void
    {
        if (count($this->steps) <= 1) {
            return;
        }

        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function saveWorkflow(SaveWorkflowAction $action): void
    {
        $project = $this->project;
        if (!$project) {
            return;
        }

        $action->execute(
            $project,
            $this->normalizedSteps(),
            $this->workflowName,
            $this->editingWorkflowId ? $this->findWorkflow($this->editingWorkflowId) : null
        );

        $this->isEditorOpen = false;
        $this->resetEditor();
        $this->dispatch('toast', message: 'چرخه ذخیره شد.', type: 'success');
    }

    public function startWorkflow(StartWorkflowAction $action, int $workflowId): void
    {
        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow) {
            return;
        }

        try {
            $action->execute($workflow);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $this->firstError($e), type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'چرخه آغاز شد.', type: 'success');
    }

    public function advanceStep(AdvanceWorkflowStepAction $action, int $workflowId, int $expectedStep, bool $approved = true): void
    {
        $advanced = $action->execute($workflowId, (int) auth()->id(), $expectedStep, $approved, false, $this->stepNotes[$workflowId] ?? null);

        if ($advanced) {
            unset($this->stepNotes[$workflowId]);
            $this->dispatch('toast', message: $approved ? 'مرحله تکمیل شد.' : 'مرحله رد شد و بازگشت داده شد.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'موردی برای تغییر یافت نشد یا وضعیت تغییر کرده است.', type: 'info');
        }
    }

    public function forceAdvance(AdvanceWorkflowStepAction $action, int $workflowId, int $expectedStep): void
    {
        $advanced = $action->execute($workflowId, (int) auth()->id(), $expectedStep, true, true);

        if ($advanced) {
            $this->dispatch('toast', message: 'پیشروی اجباری انجام شد.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'موردی برای تغییر یافت نشد یا وضعیت تغییر کرده است.', type: 'info');
        }
    }

    public function cancelWorkflow(CancelWorkflowAction $action, int $workflowId): void
    {
        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow) {
            return;
        }

        $action->execute($workflow);
        $this->dispatch('toast', message: 'چرخه لغو شد.', type: 'success');
    }

    public function saveAsTemplate(SaveAsTemplateAction $action, int $workflowId): void
    {
        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow) {
            return;
        }

        $action->execute($workflow);
        $this->dispatch('toast', message: 'به‌عنوان الگو ذخیره شد.', type: 'success');
    }

    public function applyTemplate(ApplyTemplateAction $action): void
    {
        $project = $this->project;
        $template = $this->selectedTemplateId !== '' ? WorkflowModel::templates()->find((int) $this->selectedTemplateId) : null;

        if (!$project || !$template) {
            return;
        }

        $action->execute($template, $project);
        $this->selectedTemplateId = '';
        $this->dispatch('toast', message: 'الگو اعمال شد.', type: 'success');
    }

    public function runAgain(SaveAsTemplateAction $saveAsTemplate, ApplyTemplateAction $applyTemplate, int $workflowId): void
    {
        $project = $this->project;
        $source = WorkflowModel::forProject($this->activeProjectId)
            ->whereIn('status', [WorkflowModel::STATUS_COMPLETED, WorkflowModel::STATUS_CANCELLED])
            ->find($workflowId);

        if (!$project || !$source) {
            return;
        }

        $template = $saveAsTemplate->execute($source);
        $new = null;

        try {
            $new = $applyTemplate->execute($template, $project);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $this->firstError($e), type: 'error');
            return;
        } finally {
            $new?->update(['template_id' => $source->id]);
            $template->delete();
        }

        $this->dispatch('toast', message: 'چرخه دوباره ایجاد شد.', type: 'success');
    }

    public function openReassign(int $workflowId): void
    {
        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow) {
            return;
        }

        if (!$this->isOwner && !$workflow->isAssignee((int) auth()->id())) {
            return;
        }

        $this->reassigningWorkflowId = $workflowId;
        $this->reassignSelection[$workflowId] = $workflow->currentStep()['assignee_user_ids'] ?? [];
    }

    public function closeReassign(): void
    {
        $this->reassigningWorkflowId = null;
    }

    public function reassignStep(ReassignWorkflowStepAction $action, int $workflowId): void
    {
        $workflow = $this->findWorkflow($workflowId);
        if (!$workflow) {
            return;
        }

        try {
            $action->execute($workflow, (int) auth()->id(), $this->reassignSelection[$workflowId] ?? []);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $this->firstError($e), type: 'error');
            return;
        }

        $this->reassigningWorkflowId = null;
        $this->dispatch('toast', message: 'واگذاری انجام شد.', type: 'success');
    }

    public function refreshWorkflow(): void
    {
        unset($this->activeWorkflows, $this->historyWorkflows, $this->batchedTasks, $this->batchedUserNames);
    }

    public function isStuck(WorkflowModel $workflow): bool
    {
        return (new WorkflowPresenter())->isStuck($workflow);
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.workflow', [
            'taskBoardPresenter' => new TaskBoardPresenter(),
            'workflowPresenter' => new WorkflowPresenter(),
        ]);
    }
}
