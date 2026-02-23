@php
    $config = $columnConfig[$column];
    $columnTasks = $tasks[$column] ?? [];
    $taskCount = $totalCount[$column] ?? 0;
@endphp

<div
    class="flex-1 min-w-[300px] md:min-w-[350px] h-[calc(100vh-220px)] flex flex-col gap-3 rounded-3xl bg-[var(--md-sys-color-surface-container-low)] p-3 md:p-4 shadow-sm border border-[var(--md-sys-color-outline-variant)]/40 transition-colors duration-300"
    :class="dragTask ? 'bg-[var(--md-sys-color-surface-container-highest)] border-dashed border-[var(--md-sys-color-primary)]' : ''"
    @dragover.prevent="handleDragOver($event)"
    @drop="handleDrop($event, '{{ $column }}')"
>
    <!-- Column Header -->
    <div class="flex items-center justify-between px-2 mb-2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $config['lightGradient'] }} text-white flex items-center justify-center shadow-md shadow-{{ $config['color'] }}-500/30">
                <span class="text-xl">{{ $config['icon'] }}</span>
            </div>
            <div class="flex flex-col">
                <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-lg tracking-tight">
                    {{ $config['title'] }}
                </h3>
                <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    {{ $taskCount }} مورد
                </span>
            </div>
        </div>

        <!-- Add Button (Only for TODO) -->
        @if($column === 'todo')
            <button
                @click="$dispatch('open-modal', 'create-task-modal')"
                class="p-2 rounded-full text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] hover:text-[var(--md-sys-color-primary)] transition-all active:scale-95"
                title="افزودن سریع"
            >
                <span class="material-symbols-rounded">add</span>
            </button>
        @endif
    </div>

    <!-- Tasks Container -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden space-y-3 px-1 custom-scrollbar scroll-smooth">
        @forelse($columnTasks as $task)
            @include('livewire.dashboard.taskboard.partials.card', ['task' => $task, 'column' => $column])
        @empty
            <div class="flex flex-col items-center justify-center h-48 text-[var(--md-sys-color-on-surface-variant)]/40 border-2 border-dashed border-[var(--md-sys-color-outline-variant)]/30 rounded-2xl bg-[var(--md-sys-color-surface)]/30">
                <span class="material-symbols-rounded text-4xl mb-2 opacity-50">inbox</span>
                <span class="text-sm font-medium">خالی</span>
            </div>
        @endforelse

        <!-- Drop Zone Placeholder -->
        <div x-show="dragTask && $el.closest('[data-column=\"{{ $column }}\"]')" class="h-24 rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/5 flex items-center justify-center text-[var(--md-sys-color-primary)] font-bold animate-pulse">
            رها کنید
        </div>
    </div>

    <!-- Pagination Footer -->
    @if($taskCount > $perPage)
        <div class="flex items-center justify-between pt-3 px-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
            <button
                wire:click="prevPage('{{ $column }}')"
                class="p-1.5 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] disabled:opacity-30 disabled:pointer-events-none transition-colors"
                {{ ($page[$column] ?? 1) <= 1 ? 'disabled' : '' }}
            >
                <span class="material-symbols-rounded">chevron_right</span>
            </button>

            <span class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-widest">
                {{ $page[$column] ?? 1 }} / {{ ceil($taskCount / $perPage) }}
            </span>

            <button
                wire:click="nextPage('{{ $column }}')"
                class="p-1.5 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] disabled:opacity-30 disabled:pointer-events-none transition-colors"
                {{ ($page[$column] ?? 1) >= ceil($taskCount / $perPage) ? 'disabled' : '' }}
            >
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>
    @endif
</div>
