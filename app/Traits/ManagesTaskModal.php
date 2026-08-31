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
use App\Services\ProjectTask\UpdateTaskAction;
use App\Support\TaskAccessPolicy;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    public array $staffMembers = [];
    public array $departmentOptions = [];

    public bool $isModalOpen = false;
    public bool $isEditMode = false;
    public bool $isReadOnly = false;
    public bool $showLevelUpForm = false;

    public string $newMetaKey = '';
    public string $newMetaValue = '';

    public function mountManagesTaskModal(): void
    {
        $this->staffMembers = User::getCachedActiveOptions()
            ->except(auth()->id())
            ->map(fn($name, $id) => ['id' => $id, 'full_name' => $name])
            ->values()
            ->toArray();

        $this->departmentOptions = Department::getCachedOptions()->toArray();
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
        $userId = auth()->id();

        return Task::query()
            ->where(fn(Builder $q) => $q->where('assigned_to', $userId)->orWhere('user_id', $userId))
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function customSchema(): array
    {
        if (!$this->form->projectId) return [];

        return Project::find($this->form->projectId)?->setting('custom_schema') ?? [];
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

        return $task && (in_array(auth()->id(), [$task->user_id, $task->assigned_to], true)
            || in_array(auth()->id(), $task->detail?->collaborators ?? [], true));
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

        try {
            $action->execute($task, $this->form);
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
        $this->dispatch('toast', message: 'وظیفه تکثیر شد.', type: 'success');
    }

    public function editTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status) return;

        $this->editingTaskId = $taskId;
        $this->populateFormFromTask($task);
        $this->taskReplyForm->reset();

        $this->isEditMode = true;
        $this->isReadOnly = (bool)$task->ticket_id;
        $this->isModalOpen = true;
    }

    #[Computed]
    public function editingTask(): ?Task
    {
        if (!$this->editingTaskId) return null;

        $userId = auth()->id();

        return Task::with(['replies.user', 'detail:id,task_id,collaborators'])
            ->where(fn(Builder $query) => $query->where('user_id', $userId)
                ->orWhere('assigned_to', $userId)
                ->orWhereHas('detail', fn(Builder $d) => $d->whereJsonContains('collaborators', $userId)))
            ->find($this->editingTaskId);
    }

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->isEditMode = false;
        $this->isReadOnly = false;
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

        abort_unless(auth()->id() === $task->user_id || auth()->id() === $task->assigned_to
            || in_array(auth()->id(), $task->detail?->collaborators ?? [], true), 403);

        $this->taskReplyForm->validate();

        $logger->comment($task, auth()->user(), $this->taskReplyForm->body, $this->taskReplyForm->files);

        $this->taskReplyForm->reset();
        unset($this->editingTask);
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
        $this->populateFormFromTask($task);
        $this->taskReplyForm->reset();

        $this->isEditMode = false;
        $this->isReadOnly = true;
        $this->isModalOpen = true;
    }

    protected function populateFormFromTask(Task $task): void
    {
        $this->form->newTitle = $task->title;
        $this->form->newDescription = $task->description;
        $this->form->taskUpdatedAt = $task->updated_at?->timestamp;
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
