@if($report['standalone'])
    @unless($scopeProjectId)
        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">وظایف مستقل</h3>
            @include('livewire.dashboard.tasksheet.row-table', ['row' => $report['standalone'], 'type' => 'standalone'])
        </div>
    @endunless
@endif
