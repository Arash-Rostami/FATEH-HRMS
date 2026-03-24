@php
    $config = $columnConfig[$column];
    $columnTasks = $tasks[$column] ?? [];
    $taskCount = $totalCount[$column] ?? 0;
@endphp

<div
    class="flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-[350px] h-[calc(100vh-180px)] sm:h-[calc(100vh-200px)] md:h-[calc(100vh-220px)] flex flex-col gap-3 md:gap-4 rounded-3xl bg-[var(--md-sys-color-on-primary)]  p-3 md:p-4 shadow-sm border border-[var(--md-sys-color-outline-variant)]/40 transition-all duration-300"
    :class="dragTask && $el.closest('[data-column=\" {{ $column }}\"]') ? 'bg-[var(--md-sys-color-primary-container)]/20 border-dashed border-2 border-[var(--md-sys-color-primary)] scale-[1.02]' : ''"
@dragover.prevent="handleDragOver($event)"
@drop="handleDrop($event, '{{ $column }}')"
data-column="{{ $column }}"
>

<!-- Column Header -->
<div class="flex items-center justify-between gap-3 px-2">
    <div class="flex items-center gap-3">
        <!-- Icon Container -->
        <div
            class="w-10 h-10 rounded-xl text-[var(--md-sys-color-on-{{ $config['color'] }}-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-xl">{{ $config['icon'] }}</span>
        </div>

        <!-- Title & Count -->
        <div class="flex flex-col">
            <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-base tracking-tight">
                {{ $config['title'] }}
            </h3>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    {{ $taskCount }} مورد
                </span>
        </div>
    </div>

    <!-- Add Button (Only for TO-DO) -->
    @if($column === 'todo')
        <button
            wire:click="openCreateModal"
            class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
            title="افزودن سریع"
        >
            <span class="material-symbols-rounded text-xl">add</span>
        </button>
    @endif
</div>

<!-- Tasks Container -->
<div class="flex-1 overflow-y-auto overflow-x-hidden space-y-3 px-1 scroll-smooth"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    @forelse($columnTasks as $task)
        @include('livewire.dashboard.taskboard.partials.card', ['task' => $task, 'column' => $column])
    @empty
        <!-- Empty State -->
        <div
            class="flex flex-col items-center justify-center h-48 text-[var(--md-sys-color-on-surface-variant)]/40 border-2 border-dashed border-[var(--md-sys-color-outline-variant)]/30 rounded-2xl bg-[var(--md-sys-color-surface)]/20">
            <span class="material-symbols-rounded text-5xl mb-2 opacity-40">inbox</span>
            <span class="text-sm font-medium">هیچ موردی وجود ندارد</span>
        </div>
    @endforelse

    <!-- Drop Zone Indicator (shown during drag) -->
    <div
        x-show="dragTask && $el.closest('[data-column=\" {{ $column }}\
    "]')"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    class="h-32 rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)]
    bg-[var(--md-sys-color-surface-variant)]/20 flex items-center justify-center gap-2"
    >
    <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)] animate-bounce">south</span>
    <span class="text-sm font-bold text-[var(--md-sys-color-primary)]">رها کنید</span>
</div>
</div>

<!-- Pagination Footer -->
@if($taskCount > $perPage)
    <div
        class="flex items-center justify-between gap-3 pt-3 px-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
        <button
            wire:click="prevPage('{{ $column }}')"
            class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] disabled:opacity-30 disabled:pointer-events-none transition-all duration-200 active:scale-95 flex items-center justify-center"
            {{ ($page[$column] ?? 1) <= 1 ? 'disabled' : '' }}
        >
            <span class="material-symbols-rounded">chevron_right</span>
        </button>

        <span
            class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-widest px-3 py-2 rounded-lg bg-[var(--md-sys-color-surface-container)]">
                {{ $page[$column] ?? 1 }} / {{ ceil($taskCount / $perPage) }}
            </span>

        <button
            wire:click="nextPage('{{ $column }}')"
            class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] disabled:opacity-30 disabled:pointer-events-none transition-all duration-200 active:scale-95 flex items-center justify-center"
            {{ ($page[$column] ?? 1) >= ceil($taskCount / $perPage) ? 'disabled' : '' }}
        >
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>
    @endif
    </div>
