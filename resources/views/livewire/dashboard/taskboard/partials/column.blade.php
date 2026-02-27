@php
    $config = $columnConfig[$column];
    $columnTasks = $tasks[$column] ?? [];
    $taskCount = $totalCount[$column] ?? 0;

    // Convert hardcoded gradient colors to semantic tokens per design guidelines
    $iconContainerClass = 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]';
    if ($column === 'in-progress') {
        $iconContainerClass = 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]';
    } elseif ($column === 'done') {
        $iconContainerClass = 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]';
    }
@endphp

<div class="flex-1 min-w-[300px] md:min-w-[350px] h-[calc(100vh-220px)] flex flex-col gap-3 rounded-2xl bg-[var(--md-sys-color-surface)] p-3 md:p-4 shadow-sm border border-[var(--md-sys-color-outline-variant)]/50 transition-colors duration-300"
    :class="dragTask ? 'bg-[var(--md-sys-color-surface-variant)]/20 border-dashed border-[var(--md-sys-color-primary)]' : ''"
    @dragover.prevent="handleDragOver($event)"
    @drop="handleDrop($event, '{{ $column }}')">
    <div class="flex items-center gap-3 mb-4 px-2">
        <div class="w-8 h-8 rounded-xl {{ $iconContainerClass }} flex items-center justify-center">
            <span class="material-symbols-rounded text-base">{{ $config['icon'] }}</span>
        </div>
        <h2 class="text-base font-bold">{{ $config['title'] }}</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">{{ $taskCount }} مورد</span>
        @if($column === 'todo')
            <button @click="$dispatch('open-modal', 'create-task-modal')" class="p-1.5 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all active:scale-[0.98]" title="افزودن سریع">
                <span class="material-symbols-rounded text-lg">add</span>
            </button>
        @endif
    </div>
    <div class="flex-1 overflow-y-auto overflow-x-hidden space-y-3 px-1 custom-scrollbar scroll-smooth pb-4">
        @forelse($columnTasks as $task)
            @include('livewire.dashboard.taskboard.partials.card', ['task' => $task, 'column' => $column])
        @empty
            <div class="flex flex-col items-center justify-center h-48 text-[var(--md-sys-color-on-surface-variant)]/40 border border-dashed border-[var(--md-sys-color-outline-variant)]/40 rounded-2xl bg-[var(--md-sys-color-surface-variant)]/10">
                <span class="material-symbols-rounded text-4xl mb-2 opacity-30">inbox</span>
                <span class="text-sm font-medium">خالی</span>
            </div>
        @endforelse
        <div x-show="dragTask && $el.closest('[data-column=\"{{ $column }}\"]')" class="h-24 rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)]/50 bg-[var(--md-sys-color-primary-container)]/30 flex items-center justify-center text-[var(--md-sys-color-primary)] font-bold animate-pulse shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]">
            رها کنید
        </div>
    </div>
    @if($taskCount > $perPage)
        <div class="flex items-center justify-between pt-3 px-2 border-t border-[var(--md-sys-color-outline-variant)]/30 mt-auto">
            <button wire:click="prevPage('{{ $column }}')" class="p-1.5 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 disabled:opacity-30 disabled:pointer-events-none transition-colors" {{ ($page[$column] ?? 1) <= 1 ? 'disabled' : '' }}>
                <span class="material-symbols-rounded">chevron_right</span>
            </button>
            <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-widest">
                {{ $page[$column] ?? 1 }} / {{ ceil($taskCount / $perPage) }}
            </span>
            <button wire:click="nextPage('{{ $column }}')" class="p-1.5 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 disabled:opacity-30 disabled:pointer-events-none transition-colors" {{ ($page[$column] ?? 1) >= ceil($taskCount / $perPage) ? 'disabled' : '' }}>
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>
    @endif
</div>
