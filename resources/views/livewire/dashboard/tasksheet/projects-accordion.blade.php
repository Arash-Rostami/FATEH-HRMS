@if($scopeProjectId)
    @php($row = collect($report['projects'])->firstWhere('project_id', $scopeProjectId))
    <div class="flex flex-col gap-3">
        <button type="button" wire:click="clearScope"
                class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors print:hidden">
            <span class="material-symbols-rounded text-[14px]">arrow_forward</span>
            نمایش همهٔ پروژه‌ها
        </button>
        @if($row)
            <div wire:key="tasksheet-scoped-{{ $row['project_id'] }}">
                @include('livewire.dashboard.tasksheet.row-table', ['row' => $row, 'type' => 'project'])
            </div>
        @else
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] px-1">در این بازه، فعالیتی برای این پروژه ثبت نشده است.</p>
        @endif
    </div>
@elseif(!empty($report['projects']))
    <div class="flex flex-col gap-3">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">پروژه‌ها</h3>
        <div class="flex flex-col gap-3">
            @foreach($report['projects'] as $row)
                @include('livewire.dashboard.tasksheet.row-table', ['row' => $row, 'type' => 'project'])
            @endforeach
        </div>
    </div>
@endif
