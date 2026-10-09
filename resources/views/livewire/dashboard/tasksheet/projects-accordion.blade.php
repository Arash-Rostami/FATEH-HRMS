@if($scopeProjectId)
    @php($row = collect($report['projects'])->firstWhere('project_id', $scopeProjectId))
    <div wire:key="tasksheet-projects-scoped" class="flex flex-col gap-3">
        @if($row)
            <div wire:key="tasksheet-scoped-{{ $row['project_id'] }}">
                @include('livewire.dashboard.tasksheet.row-table', ['row' => $row, 'type' => 'project'])
            </div>
        @else
            <p wire:key="tasksheet-projects-scoped-empty" class="text-xs text-[var(--md-sys-color-on-surface-variant)] px-1">در این بازه، فعالیتی برای این پروژه ثبت نشده است.</p>
        @endif
    </div>
@elseif(!empty($report['projects']))
    <div wire:key="tasksheet-projects-all" class="flex flex-col gap-3">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">پروژه‌ها</h3>
        <div class="flex flex-col gap-3">
            @foreach($report['projects'] as $row)
                @include('livewire.dashboard.tasksheet.row-table', ['row' => $row, 'type' => 'project'])
            @endforeach
        </div>
    </div>
@endif
