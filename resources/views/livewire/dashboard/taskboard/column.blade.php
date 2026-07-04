@php
    $config = $presenter->columnConfig()[$column];
    $columnTasks = $tasks[$column] ?? [];
    $taskCount = $totalCount[$column] ?? 0;
@endphp

<div
    data-column="{{ $column }}"
    @dragover.prevent="handleDragOver($event)"
    @drop="handleDrop($event, col($el))"
    :class="{
        'bg-[var(--md-sys-color-primary-container)]/20 !border-dashed !border-2 !border-[var(--md-sys-color-primary)] scale-[1.02]': dragTask,
        'max-widget-column': maximizedColumn === $el.dataset.column
    }"
    class="flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-[350px] max-h-[calc(100vh-220px)] min-h-[320px] flex flex-col gap-3 md:gap-4 rounded-3xl bg-[var(--md-sys-color-on-primary)] p-3 md:p-4 shadow-sm border border-[var(--md-sys-color-outline-variant)]/40 transition-all duration-300"
>
    <!-- Column Header -->
    <div class="flex items-center justify-between gap-3 px-2">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl text-[var(--md-sys-color-on-{{ $config['color'] }}-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-xl">{{ $config['icon'] }}</span>
            </div>

            <div class="flex flex-col">
                <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-base tracking-tight">
                    {{ $config['title'] }}
                </h3>
                <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    {{ $taskCount }} مورد
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <button
                class="ripple-effect min-w-[36px] min-h-[36px] p-1.5 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                @click="toggleMaximize(col($el))"
                :title="maximizedColumn === col($el) ? 'کوچک کردن' : 'بزرگ کردن'"
                :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': maximizedColumn === col($el) }"
            >
                <span
                    class="material-symbols-rounded text-lg transition-transform duration-300"
                    x-text="maximizedColumn === col($el) ? 'close_fullscreen' : 'open_in_full'"
                ></span>
            </button>

            <button
                class="ripple-effect min-w-[36px] min-h-[36px] p-1.5 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                @click="toggleCollapsed(col($el))"
                title="جمع/باز کردن ستون"
            >
                <span
                    class="material-symbols-rounded text-lg transition-transform duration-300"
                    :class="isCollapsed(col($el)) ? 'rotate-180' : ''"
                >expand_more</span>
            </button>

            @if($column === 'todo')
                <button
                    wire:click="openCreateModal"
                    class="ripple-effect min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="افزودن سریع"
                >
                    <span class="material-symbols-rounded text-xl">add</span>
                </button>
            @endif
        </div>
    </div>

    <div x-show="!isCollapsed(col($el))" class="flex-1 flex flex-col gap-3 md:gap-4 min-h-0">
        <!-- Tasks Container -->
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden rounded-2xl bg-[var(--md-sys-color-surface-variant)]/30 p-2 {{ $density === 'compact' ? 'space-y-1.5' : 'space-y-3' }} scroll-smooth container-scrollbar custom-scrollbar">
            @forelse($columnTasks as $task)
                @include('livewire.dashboard.taskboard.card', ['task' => $task, 'column' => $column])
            @empty
                <x-ui.empty icon="inbox" title="هیچ موردی وجود ندارد" description="با افزودن وظیفه، کارت‌ها اینجا نمایش داده می‌شوند" variant="list" />
            @endforelse

            <!-- Drop Zone Indicator -->
            <div
                x-show="dragTask"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="h-32 rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface-variant)]/20 flex items-center justify-center gap-2"
            >
                <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)] animate-bounce">south</span>
                <span class="text-sm font-bold text-[var(--md-sys-color-primary)]">رها کنید</span>
            </div>
        </div>

        @if($taskCount > $perPage)
            <div class="flex items-center justify-between gap-3 pt-3 px-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
                <button
                    wire:click="prevPage('{{ $column }}')"
                    class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] disabled:opacity-30 disabled:pointer-events-none transition-all duration-200 active:scale-95 flex items-center justify-center"
                    {{ ($page[$column] ?? 1) <= 1 ? 'disabled' : '' }}
                >
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>

                <span class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-widest px-3 py-2 rounded-lg bg-[var(--md-sys-color-surface-container)]">
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
</div>
