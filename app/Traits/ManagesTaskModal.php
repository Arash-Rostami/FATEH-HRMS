<?php

namespace App\Traits;

use App\Enums\TaskActivityType;
use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Livewire\Dashboard\TaskBoard\Forms\ReplyForm;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Department;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\ApproveTaskAction;
use App\Services\ProjectTask\CreateTaskAction;
use App\Services\ProjectTask\DuplicateTaskAction;
use App\Services\ProjectTask\ReportingService;
use App\Services\ProjectTask\UpdateTaskAction;
use App\Support\TaskAccessPolicy;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ManagesTaskModal
{
    public TaskForm $form;
    public ReplyForm $taskReplyForm;
    public ProjectForm $levelUpForm;

    #[Locked]
    public ?int $editingTaskId = null;
    #[Locked]
    public bool $editingTaskHasProject = false;
    public array $staffMembers = [];
    public array $departmentOptions = [];

    public string $userSearch = '';
    public string $userDept = '';
    #[Locked]
    public int $userPickerLimit = 12;

    public bool $isModalOpen = false;
    public bool $isEditMode = false;
    public bool $isReadOnly = false;
    public bool $showLevelUpForm = false;

    public string $newMetaKey = '';
    public string $newMetaValue = '';

    public function mountManagesTaskModal(): void
    {
        $this->staffMembers = User::getCachedActiveOptions()
            ->map(fn($name, $id) => ['id' => $id, 'full_name' => $name])
            ->values()
            ->toArray();

        $this->departmentOptions = Department::getCachedOptions()->toArray();
    }

    public function loadMoreUsers(): void
    {
        $this->userPickerLimit += 12;
    }

    public function resetUserPicker(): void
    {
        $this->userSearch = '';
        $this->userDept = '';
        $this->userPickerLimit = 12;
    }

    #[Computed]
    public function formProject(): ?Project
    {
        return $this->form->projectId ? Project::find($this->form->projectId) : null;
    }

    #[Computed]
    public function userPickerResults(): array
    {
        $selected = array_map('intval', array_values(array_unique(array_filter([
            $this->form->selectedAssignee,
            $this->form->responsibleUserId,
            ...$this->form->collaborators,
        ], fn($id) => $id !== null))));

        $limit = $this->userPickerLimit;

        $users = User::query()
            ->visibleOnBoard()
            ->when($this->formProject, fn(Builder $q, Project $project) => $q->where(fn(Builder $roster) => $roster
                ->whereIn('users.id', $project->charter()->rosterIds())
                ->orWhereIn('users.id', $selected)))
            ->where(function (Builder $q) use ($selected) {
                $q->where(function (Builder $inner) {
                    $inner->when($this->userDept !== '',
                        fn(Builder $w) => $w->whereHas('profile', fn(Builder $p) => $p->where('department_id', $this->userDept)));
                    $inner->when($this->userSearch !== '',
                        fn(Builder $w) => $w->search($this->userSearch));
                });
                if ($selected !== []) {
                    $q->orWhereIn('users.id', $selected);
                }
            })
            ->when($selected !== [], fn(Builder $q) => $q->orderByRaw('CASE WHEN users.id IN (' . implode(',', $selected) . ') THEN 0 ELSE 1 END'))
            ->orderBy('name')
            ->limit($limit + 1)
            ->get(['id', 'name']);

        return [
            'candidates' => $users->take($limit)
                ->map(fn(User $user) => ['id' => $user->id, 'full_name' => $user->name])
                ->values()
                ->all(),
            'hasMore' => $users->count() > $limit,
        ];
    }

    #[Computed]
    public function availableSections(): array
    {
        return $this->selectedDepartment?->sectionsOptions() ?? [];
    }

    #[Computed]
    public function availableUnits(): array
    {
        return $this->selectedDepartment?->unitsOptions() ?? [];
    }

    #[Computed]
    public function labelOptions(): array
    {
        if ($labels = $this->formProject?->charter()->labels()) {
            return $labels;
        }

        $userId = auth()->id();
        $projectId = property_exists($this, 'projectFilter') ? $this->projectFilter : null;

        return app(ReportingService::class)->labelOptions(
            fn(Builder $q) => $q
                ->when($projectId, fn(Builder $p) => $p->where('project_id', $projectId))
                ->where(fn(Builder $o) => $o->ownedBy($userId)->orWhere('user_id', $userId)),
            'u' . $userId . ($projectId ? 'p' . $projectId : ''),
            $projectId
        );
    }

    #[Computed]
    public function customSchema(): array
    {
        return $this->formProject?->charter()->customSchema() ?? [];
    }

    public function addMetaKey(): void
    {
        $key = strtolower(trim($this->newMetaKey));

        if ($key === '' || !preg_match('/^[a-z0-9_]+$/', $key)) {
            $this->dispatch('toast', message: 'کلید دیتای سفارشی فقط می‌تواند شامل حروف کوچک انگلیسی، رقم و زیرخط باشد.', type: 'error');
            return;
        }

        $this->form->meta[$key] = $this->newMetaValue;
        $this->newMetaKey = '';
        $this->newMetaValue = '';
    }

    public function removeMetaKey(string $key): void
    {
        unset($this->form->meta[$key]);
    }

    #[Computed]
    public function canReplyToTask(): bool
    {
        $task = $this->editingTask;

        return $task && TaskAccessPolicy::canReply($task, auth()->user());
    }

    #[Computed]
    public function taskComments(): EloquentCollection
    {
        $task = $this->editingTask;

        return $task
            ? $task->replies->whereIn('type', [TaskActivityType::Comment, TaskActivityType::Attachment])->values()
            : new EloquentCollection();
    }

    #[Computed]
    public function taskHistory(): array
    {
        $task = $this->editingTask;
        if (!$task) {
            return [];
        }

        $logger = app(ActivityLogger::class);

        return $task->replies
            ->reject(fn(Reply $reply) => in_array($reply->type, [TaskActivityType::Comment, TaskActivityType::Attachment], true))
            ->map(fn(Reply $reply) => [
                'id' => $reply->id,
                'created_at' => $reply->created_at->toIso8601String(),
                ...$logger->render($reply),
            ])
            ->values()
            ->all();
    }

    public function createTask(CreateTaskAction $action): void
    {
        try {
            $action->execute($this->form);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        } catch (Exception $e) {
            report($e);
            $this->addError('form', 'عملیات با خطا مواجه شد؛ دوباره تلاش کنید.');
            return;
        }

        $this->form->reset();
        $this->refreshAfterTaskSave();
        $this->isModalOpen = false;
        $this->dispatch('task-created');
        $this->dispatch('toast', message: 'وظیفه با موفقیت اضافه شد.', type: 'success');
    }

    public function approveTask(ApproveTaskAction $action, int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task) return;

        if ($action->execute($task, auth()->user())) {
            $this->refreshAfterTaskSave();
            $this->dispatch('toast', message: 'وظیفه تأیید شد.', type: 'success');
        }
    }

    public function duplicateTask(DuplicateTaskAction $action, int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status || $task->ticket_id || $task->is_archived) return;

        if (!Cache::add('task-duplicate:' . auth()->id() . ':' . $taskId, 1, 5)) return;

        try {
            $action->execute($task, $this->form);
        } catch (Exception $e) {
            $this->form->reset();
            $e instanceof ValidationException || report($e);
            $this->dispatch('toast', message: $e instanceof ValidationException ? collect($e->errors())->flatten()->first() : 'عملیات با خطا مواجه شد؛ دوباره تلاش کنید.', type: 'error');
            return;
        }

        $this->form->reset();
        $this->refreshAfterTaskSave();
        $this->dispatch('toast', message: 'وظیفه تکثیر شد.', type: 'success');
    }

    public function editTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status) return;

        $this->editingTaskId = $taskId;
        $this->editingTaskHasProject = $task->project_id !== null;
        $this->populateFormFromTask($task);
        $this->taskReplyForm->reset();
        $this->resetValidation();

        $this->isEditMode = true;
        $this->isReadOnly = (bool)$task->ticket_id;
        $this->resetUserPicker();
        $this->isModalOpen = true;
    }

    #[Computed]
    public function editingTask(): ?Task
    {
        if (!$this->editingTaskId) return null;

        $task = Task::with(['replies.user', 'detail:id,task_id,collaborators,responsible_user_id', 'project:id,owner_id,member_ids,departments'])
            ->find($this->editingTaskId);

        return $task && TaskAccessPolicy::canView($task, auth()->user()) ? $task : null;
    }

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->editingTaskId = null;
        $this->editingTaskHasProject = false;
        $this->taskReplyForm->reset();
        $this->resetValidation();
        $this->isEditMode = false;
        $this->isReadOnly = false;
        $this->resetUserPicker();
        $this->isModalOpen = true;
        $this->afterOpenCreateModal();
    }

    public function toggleLevelUpForm(): void
    {
        $this->showLevelUpForm = !$this->showLevelUpForm;

        if ($this->showLevelUpForm && $this->levelUpForm->name === '') {
            $this->levelUpForm->name = $this->form->newTitle;
            $this->levelUpForm->memberIds = array_values(array_unique(array_filter([
                $this->form->selectedAssignee,
                ...$this->form->collaborators,
            ])));
            $this->levelUpForm->departments = $this->form->departmentId ? [$this->form->departmentId] : [];
        }
    }

    public function createProjectFromTask(): void
    {
        $this->levelUpForm->validate();

        $this->form->pendingProjectName = $this->levelUpForm->name;
        $this->form->pendingProjectMemberIds = $this->levelUpForm->memberIds;
        $this->form->pendingProjectDepartments = $this->levelUpForm->departments;

        $this->levelUpForm->reset();
        $this->showLevelUpForm = false;
        $this->dispatch('toast', message: 'پروژه هنگام ذخیرهٔ وظیفه ساخته و پیوند می‌شود.', type: 'success');
    }

    public function cancelPendingProject(): void
    {
        $this->form->pendingProjectName = null;
        $this->form->pendingProjectMemberIds = [];
        $this->form->pendingProjectDepartments = [];
    }

    protected function afterOpenCreateModal(): void
    {
    }

    protected function collaboratorLookup(EloquentCollection $tasks): array
    {
        return app(\App\Services\ProjectTask\BoardCollaboratorResolver::class)->resolve($tasks);
    }

    public function postTaskReply(ActivityLogger $logger): void
    {
        $task = $this->editingTask;

        if (!$task) return;

        abort_unless(TaskAccessPolicy::canReply($task, auth()->user()), 403);

        $this->taskReplyForm->validate();

        $logger->comment($task, auth()->user(), $this->taskReplyForm->body, $this->taskReplyForm->files);

        $this->taskReplyForm->reset();
        unset($this->editingTask);
        $this->refreshAfterTaskSave();
        $this->dispatch('toast', message: 'پاسخ شما ثبت شد.', type: 'success');
    }

    #[Computed]
    public function projectOptions(): array
    {
        return Project::visibleTo(auth()->user())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function removeAttachment(int $index): void
    {
        unset($this->form->attachments[$index]);
        $this->form->attachments = array_values($this->form->attachments);
    }

    public function removeReplyAttachment(int $index): void
    {
        unset($this->taskReplyForm->files[$index]);
        $this->taskReplyForm->files = array_values($this->taskReplyForm->files);
    }

    #[Computed]
    public function selectedDepartment(): ?Department
    {
        return Department::getCachedModels()->get($this->form->departmentId);
    }

    public function updateTask(UpdateTaskAction $action): void
    {
        $task = Task::find($this->editingTaskId);
        if (!$task) return;

        if ((int) $this->form->projectId !== (int) $task->project_id && $this->refuseIfCycleLocked($task)) return;

        try {
            $action->execute($task, $this->form);
        } catch (ValidationException $e) {
            if ($message = $e->errors()['form'][0] ?? null) {
                $this->dispatch('toast', message: $message, type: 'error');
            }
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        } catch (Exception $e) {
            report($e);
            $this->addError('form', 'عملیات با خطا مواجه شد؛ دوباره تلاش کنید.');
            return;
        }

        $this->form->reset();
        $this->editingTaskId = null;
        $this->isModalOpen = false;
        $this->refreshAfterTaskSave();
        $this->dispatch('toast', message: 'وظیفه با موفقیت بروزرسانی شد.', type: 'success');
    }

    protected function refuseIfCycleLocked(?Task $task): bool
    {
        if (!$task?->isCycleLocked()) return false;

        $this->dispatch('toast', message: 'این وظیفه به مرحلهٔ جاری چرخه متصل است؛ ابتدا مرحله را پیش ببرید یا چرخه را لغو کنید.', type: 'error');

        return true;
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form->unit = null;
        $this->form->section = null;
    }

    public function viewTask(int $taskId): void
    {
        $task = Task::with('detail')->find($taskId);

        if (!$task || !TaskAccessPolicy::canView($task, auth()->user())) return;

        $this->editingTaskId = $taskId;
        $this->editingTaskHasProject = $task->project_id !== null;
        $this->populateFormFromTask($task);
        $this->taskReplyForm->reset();
        $this->resetValidation();

        $this->isEditMode = false;
        $this->isReadOnly = true;
        $this->resetUserPicker();
        $this->isModalOpen = true;
    }

    protected function populateFormFromTask(Task $task): void
    {
        $this->form->newTitle = $task->title;
        $this->form->newDescription = $task->description;
        $this->form->taskUpdatedAt = $task->updated_at?->timestamp;
        $this->form->taskVersion = $task->version;
        $this->form->selectedAssignee = $task->assigned_to;
        $this->form->projectId = $task->project_id;
        $this->form->pendingProjectName = null;
        $this->form->pendingProjectMemberIds = [];
        $this->form->pendingProjectDepartments = [];
        $this->form->labels = $task->labels ?? [];
        $this->form->priority = $task->priority?->value;

        if ($task->deadline) {
            $jDate = Jalalian::fromCarbon($task->deadline);
            $this->form->deadlineYear = $jDate->getYear();
            $this->form->deadlineMonth = $jDate->getMonth();
            $this->form->deadlineDay = $jDate->getDay();
        } else {
            $this->form->reset(['deadlineYear', 'deadlineMonth', 'deadlineDay']);
        }

        $detail = $task->detail;
        $this->form->departmentId = $detail?->department_id;
        $this->form->unit = $detail?->unit;
        $this->form->section = $detail?->section;
        $this->form->project = $detail?->project;
        $this->form->scheme = $detail?->scheme;
        $this->form->actionSourceDomain = $detail?->action_source_domain;
        $this->form->actionSource = $detail?->action_source;
        $this->form->collaborators = $detail?->collaborators ?? [];
        $this->form->responsibleUserId = $detail?->responsible_user_id;
        $this->form->state = $detail?->state;
        $this->form->checklist = $detail?->checklist ?? [];
        $this->form->meta = $detail?->meta ?? [];
        $this->form->attachments = [];
        $this->form->existingAttachments = $detail?->attachments ?? [];
        $this->form->detailUpdatedAt = $detail?->updated_at?->timestamp;
    }

    abstract protected function refreshAfterTaskSave(): void;
}
