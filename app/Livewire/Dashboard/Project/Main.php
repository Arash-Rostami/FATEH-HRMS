<?php

namespace App\Livewire\Dashboard\Project;

use App\Jobs\SyncProjectChannelMembershipJob;
use App\Livewire\Dashboard\Project\Actions\UpdateProjectAction;
use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Models\Channel;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\ChannelProvisioner;
use App\Services\ProjectTask\CreateProjectAction;
use App\Services\ProjectTask\ProjectHeartbeat;
use App\Services\ProjectTask\ProjectSettings;
use App\Services\ProjectTask\ReportingService;
use App\Traits\FocusOnRecord;
use App\Traits\HasProjectMembers;
use App\Traits\HasReportSummary;
use App\Traits\ManagesTaskModal;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Lazy]
class Main extends Component
{
    use FocusOnRecord, ManagesTaskModal, HasReportSummary, HasProjectMembers;

    private const PROJECT_LIST_COLUMNS = [
        'projects.id',
        'projects.name',
        'projects.channel_id',
        'channel_members.user_id as cm_user_id',
        'channel_members.entered_at',
    ];
    private const TAB_DOMAIN = ['activity' => 'activity', 'teamChat' => 'chat', 'projectCalendar' => 'task'];

    #[Locked]
    public ?int $activeProjectId = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'activity';
    public string $search = '';
    public int $projectsLimit = 30;

    public ProjectForm $projectForm;
    public bool $isFormOpen = false;
    public bool $isEditing = false;
    public array $tabDirty = ['activity' => false, 'teamChat' => false, 'projectCalendar' => false];

    #[Computed]
    protected function visibleProjectsWithMembership(): Collection
    {
        return Project::visibleTo(auth()->user())
            ->orderBy('name')
            ->leftJoin('channel_members', function ($j) {
                $j->on('channel_members.channel_id', '=', 'projects.channel_id')
                    ->where('channel_members.user_id', auth()->id());
            })
            ->get(self::PROJECT_LIST_COLUMNS);
    }

    #[Computed]
    public function myProjects(): array
    {
        $needle = mb_strtolower(trim($this->search));

        $filtered = $this->visibleProjectsWithMembership
            ->when($needle !== '', fn($rows) => $rows->filter(
                fn($p) => str_contains(mb_strtolower($p->name), $needle)
            ))
            ->values();

        $page = $filtered->take($this->projectsLimit);
        $counts = Task::whereIn('project_id', $page->pluck('id'))
            ->selectRaw('project_id, status, count(*) as aggregate')
            ->groupBy('project_id', 'status')
            ->get()
            ->groupBy('project_id');

        return [
            'rows' => $page
                ->map(function ($p) use ($counts) {
                    $byStatus = ($counts->get($p->id) ?? collect())->pluck('aggregate', 'status');
                    $total = (int) $byStatus->sum();
                    $done = (int) ($byStatus['done'] ?? 0);

                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'pending' => (bool) ($p->channel_id && $p->cm_user_id && !$p->entered_at),
                        'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
                    ];
                })
                ->values()
                ->all(),
            'hasMore' => $filtered->count() > $this->projectsLimit,
            'total' => $filtered->count(),
        ];
    }

    public function loadMoreProjects(): void
    {
        $this->projectsLimit += 30;
        unset($this->myProjects);
    }

    public function updatedSearch(): void
    {
        $this->projectsLimit = 30;
    }

    #[Computed]
    public function pendingInviteProjects(): array
    {
        return $this->visibleProjectsWithMembership
            ->filter(fn($p) => $p->channel_id && $p->cm_user_id && !$p->entered_at)
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();
    }

    #[Computed]
    public function tabs(): array
    {
        return (new ProjectPresenter())->tabs($this->activeProjectId);
    }

    #[Computed]
    public function activeProject(): ?Project
    {
        return $this->activeProjectId
            ? Project::visibleTo(auth()->user())->with(['owner:id,name', 'owner.profile:id,user_id,image'])->find($this->activeProjectId)
            : null;
    }

    #[Computed]
    public function activeChannel(): ?Channel
    {
        $project = $this->activeProject;
        return $project ? app(ChannelProvisioner::class)->resolve($project) : null;
    }

    #[Computed]
    public function projectMembers(): array
    {
        $project = $this->activeProject;
        if (!$project) {
            return [];
        }

        return $this->projectMemberUsers
            ->map(function (User $user) use ($project) {
                $profile = $user->profile;
                $occasionType = $profile?->todaysOccasionType();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->getProfileImageUrl(),
                    'is_owner' => $user->id === $project->owner_id,
                    'presence_label' => $user->presence?->label(),
                    'presence_class' => $user->presence?->activeClass(),
                    'occasion' => $occasionType ? \App\Models\Profile::occasionTone($occasionType) : null,
                    'org_title' => trim(collect([$profile?->unit, $profile?->section])->filter()->implode(' › ')),
                ];
            })
            ->sortByDesc('is_owner')
            ->values()
            ->all();
    }

    public function focusRecord(int $projectId): bool
    {
        $requestedTab = $this->activeTab;

        $handled = $this->selectProject($projectId);

        if (!$handled) {
            return false;
        }

        $this->switchTab($requestedTab);

        $focusEntry = (int) request()->query('focus_entry', 0);
        if ($focusEntry > 0) {
            $project = $this->activeProject;

            return $project && app(ActivityLogger::class)->feedFor($project)->where('id', $focusEntry)->exists();
        }

        return true;
    }

    public function selectProject(int $projectId): bool
    {
        $project = Project::visibleTo(auth()->user())->find($projectId);
        if (!$project) {
            return false;
        }

        $this->activeProjectId = $projectId;
        $this->activeTab = 'activity';
        $this->tabDirty = ['activity' => false, 'teamChat' => false, 'projectCalendar' => false];

        $channel = $this->activeChannel;
        $this->markEntered($channel);

        unset(
            $this->activeProject,
            $this->activeChannel,
            $this->projectMembers,
            $this->projectMemberIds,
            $this->projectMemberUsers,
            $this->mentionCandidates,
            $this->reportSummary,
            $this->visibleProjectsWithMembership,
            $this->myProjects,
            $this->pendingInviteProjects
        );

        return true;
    }

    public function refreshSidebar(): void
    {
    }

    private function markEntered(Channel $channel): void
    {
        $channel->memberUsers()->newPivotStatementForId((int) auth()->id())
            ->whereNull('entered_at')
            ->update(['entered_at' => now(), 'updated_at' => now()]);
    }

    public function openProjectTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->find($taskId);

        if (!$task) return;

        $this->editingTaskId = $taskId;
        $this->populateFormFromTask($task);
        $this->taskReplyForm->reset();

        $this->isEditMode = $task->can_change_status;
        $this->isReadOnly = !$task->can_change_status;
        $this->isModalOpen = true;
    }

    protected function refreshAfterTaskSave(): void
    {
        $this->dispatch('project-kanban-refresh');
    }

    protected function afterOpenCreateModal(): void
    {
        $this->form->projectId = $this->activeProjectId;
    }

    public function switchTab(string $tab): void
    {
        if (!in_array($tab, ['activity', 'teamChat', 'projectCalendar', 'kanban', 'report', 'analytics'], true)) {
            return;
        }

        $this->activeTab = $tab;

        if (array_key_exists($tab, $this->tabDirty)) {
            $this->tabDirty[$tab] = false;
        }

        if ($tab === 'teamChat' && $this->activeProject) {
            (new SyncProjectChannelMembershipJob($this->activeProject->id))->handle(app(ChannelProvisioner::class));
            $this->dispatch('project-teamchat-activate');
        }
    }

    #[Renderless]
    public function warm(string $tab): void
    {
        if (!$this->activeProjectId || !in_array($tab, ['activity', 'teamChat', 'projectCalendar', 'kanban', 'report', 'analytics'], true)) {
            return;
        }

        $service = app(ReportingService::class);

        match ($tab) {
            'report' => $service->summary($this->activeProjectId, (int) auth()->id()),
            'analytics' => $service->analyticsInsights($this->activeProjectId),
            default => null,
        };
    }

    public function markTabsDirtyExcept(string $activeTab, ?array $domains = null): void
    {
        $domains ??= ProjectHeartbeat::DOMAINS;

        foreach (array_keys($this->tabDirty) as $tab) {
            if ($tab === $activeTab) {
                continue;
            }

            if (in_array(self::TAB_DOMAIN[$tab] ?? null, $domains, true)) {
                $this->tabDirty[$tab] = true;
            }
        }
    }

    #[Renderless]
    public function heartbeat(): string
    {
        if (!$this->activeProjectId) {
            return '0';
        }

        if ($this->activeTab === 'teamChat' && !$this->isStillChannelMember()) {
            return 'gone';
        }

        return ProjectHeartbeat::version($this->activeProjectId);
    }

    private function isStillChannelMember(): bool
    {
        $channel = $this->activeChannel;
        if (!$channel) {
            return false;
        }

        return $channel->memberUsers()->where('users.id', auth()->id())->exists();
    }

    #[Computed]
    public function memberCandidates(): array
    {
        return User::getCachedActiveOptions()
            ->except(auth()->id())
            ->map(fn($name, $id) => ['id' => (int) $id, 'name' => $name])
            ->values()
            ->all();
    }

    public function openCreate(): void
    {
        $this->projectForm->reset();
        $this->isEditing = false;
        $this->isFormOpen = true;
    }

    public function openEdit(): void
    {
        $project = $this->activeProject;
        if (!$project || $project->owner_id !== auth()->id()) {
            return;
        }

        $this->projectForm->fill([
            'name' => $project->name,
            'memberIds' => $project->member_ids ?? [],
            'departments' => $project->departments ?? [],
            'updatedAt' => $project->updated_at?->timestamp,
        ]);
        app(ProjectSettings::class)->fillForm($this->projectForm, $project);
        $this->isEditing = true;
        $this->isFormOpen = true;
    }

    public function addSchemaRow(): void
    {
        $this->projectForm->customSchema[] = ['key' => '', 'label' => ''];
    }

    public function removeSchemaRow(int $index): void
    {
        unset($this->projectForm->customSchema[$index]);
        $this->projectForm->customSchema = array_values($this->projectForm->customSchema);
    }

    public function addExtraSettingRow(): void
    {
        $this->projectForm->extraSettings[] = ['key' => '', 'value' => ''];
    }

    public function removeExtraSettingRow(int $index): void
    {
        unset($this->projectForm->extraSettings[$index]);
        $this->projectForm->extraSettings = array_values($this->projectForm->extraSettings);
    }

    public function closeForm(): void
    {
        $this->isFormOpen = false;
    }

    public function createProject(CreateProjectAction $action): void
    {
        $project = $action->execute($this->projectForm);

        $this->isFormOpen = false;
        unset($this->myProjects, $this->visibleProjectsWithMembership);
        $this->selectProject($project->id);
        $this->dispatch('show-toast', message: 'پروژه ایجاد شد', type: 'success');
    }

    public function updateProject(UpdateProjectAction $action): void
    {
        $project = $this->activeProject;
        if (!$project) {
            return;
        }

        try {
            $action->execute($project, $this->projectForm);
        } catch (ValidationException $e) {
            if ($message = $e->errors()['form'][0] ?? null) {
                $this->dispatch('show-toast', message: $message, type: 'error');
            }
            throw $e;
        }

        $this->isFormOpen = false;
        unset($this->myProjects, $this->visibleProjectsWithMembership, $this->activeProject, $this->activeChannel);
        $this->dispatch('show-toast', message: 'پروژه به‌روزرسانی شد', type: 'success');
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    public function render(): View
    {
        if (!in_array($this->activeTab, ['activity', 'teamChat', 'projectCalendar', 'kanban', 'report', 'analytics'], true)) {
            $this->activeTab = 'activity';
        }

        $presenter = new ProjectPresenter();

        return view('livewire.dashboard.project', [
            'presenter' => $presenter,
            'taskBoardPresenter' => new TaskBoardPresenter(),
        ])
            ->extends('layouts.app')
            ->section('content');
    }

    protected function recordFocusType(): string
    {
        return 'project';
    }
}
