@php
    $isOwner = $this->isOwner;
    $activeWorkflows = $this->activeWorkflows;
    $historyWorkflows = $this->historyWorkflows;
    $templates = $this->templates;
    $memberCandidates = $this->memberCandidates;
    $memberNames = collect($memberCandidates)->pluck('name', 'id');
    $batchedTasks = $this->batchedTasks;
    $batchedUserNames = $this->batchedUserNames;
@endphp

<div class="flex flex-col gap-4" wire:key="workflow-{{ $activeProjectId }}" @project-workflow-refresh.window="$wire.refreshWorkflow()">
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">چرخه‌های کاری</p>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">توالی مراحل تحویل کار با محول‌شونده مشخص و پیگیری خودکار</p>
        </div>

        @if($isOwner)
            <div wire:key="workflow-owner-toolbar" class="flex flex-wrap items-center gap-2">
                @if($templates->isNotEmpty())
                    <div wire:key="workflow-apply-template" class="flex items-center gap-2">
                        <div class="w-40">
                            <x-ui.forms.select label="اعمال الگو" name="selectedTemplateId" wire:model="selectedTemplateId">
                                <option value=""></option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                        <x-ui.buttons.form wire:click="applyTemplate" variant="tonal" icon="content_copy" size="icon" title="اعمال الگو"/>
                    </div>
                @endif

                <x-ui.buttons.form wire:click="openNewWorkflow" icon="add_circle">چرخه جدید</x-ui.buttons.form>
            </div>
        @endif
    </div>

    @if($isEditorOpen)
        <div wire:key="workflow-editor" class="contents">
            @include('livewire.dashboard.project.workflow.editor', ['memberCandidates' => $memberCandidates])
        </div>
    @endif

    @if($activeWorkflows->isEmpty() && !$isEditorOpen)
        <div wire:key="workflow-active-empty" class="contents">
            <x-ui.empty icon="conversion_path" title="هنوز چرخهٔ کاری‌ای برای این پروژه تعریف نشده"
                        description="{{ $isOwner ? 'با دکمهٔ «چرخه جدید» یک توالی از مراحل تحویل کار بسازید یا الگویی را اعمال کنید.' : 'مدیر پروژه می‌تواند یک چرخهٔ کاری تعریف کند.' }}"/>
        </div>
    @else
        <div wire:key="workflow-active-list" class="space-y-3">
            @foreach($activeWorkflows as $wf)
                @include('livewire.dashboard.project.workflow.card', [
                    'wf' => $wf,
                    'isOwner' => $isOwner,
                    'memberCandidates' => $memberCandidates,
                    'memberNames' => $memberNames,
                    'batchedTasks' => $batchedTasks,
                    'batchedUserNames' => $batchedUserNames,
                    'taskBoardPresenter' => $taskBoardPresenter,
                    'workflowPresenter' => $workflowPresenter,
                ])
            @endforeach
        </div>
    @endif

    @if($historyWorkflows->isNotEmpty())
        @include('livewire.dashboard.project.workflow.history', ['historyWorkflows' => $historyWorkflows, 'isOwner' => $isOwner])
    @endif
</div>
