@php
    $colState = $presenter->columnState($task['status']);
@endphp

<div
    :draggable="{{ $task['can_change_status'] ? 'true' : 'false' }}"
    @dragstart="{{ $task['can_change_status'] ? 'handleDragStart($event, '. $task['id'] . ')' : 'event.preventDefault()' }}"
    @dragend="handleDragEnd($event)"
    class="group relative flex flex-col {{ $density === 'compact' ? 'gap-2 p-2.5 md:p-3 pt-5' : 'gap-3 p-4 md:p-5 pt-6' }} rounded-2xl animate-bubble-in {{ $task['status'] === 'pending' ? 'bg-[var(--md-sys-color-error-container)]/30 border-[var(--md-sys-color-error)] border-2 shadow-[0_0_15px_color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] animate-pulse-slow' : 'bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:border-[var(--md-sys-color-primary)]/40' }} hover:-translate-y-1 transition-all duration-300 {{ $task['can_change_status'] ? 'cursor-grab active:cursor-grabbing' : 'cursor-default opacity-80' }} active:scale-[0.98] select-none"
    dir="rtl"
>
    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-32 md:w-40 h-[2px] rounded-b-full opacity-80 group-hover:opacity-100 transition-opacity bg-gradient-to-r {{ $colState['lightGradient'] }} animate-subtle-pulse" style="box-shadow: 0 4px 12px color-mix(in srgb, var(--md-sys-color-{{ $colState['color'] }}) 40%, transparent);"></div>

    @if($selectionMode)
        <button
            wire:click="toggleTaskSelection({{ $task['id'] }})"
            class="absolute top-3 right-3 min-w-[28px] min-h-[28px] rounded-lg border-2 flex items-center justify-center transition-all duration-150 {{ in_array($task['id'], $selectedTasks) ? 'bg-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]' : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]' }}"
        >
            @if(in_array($task['id'], $selectedTasks))
                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary)]">check</span>
            @endif
        </button>
    @endif

    <div class="flex items-start justify-between gap-3 mt-1">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2 flex-1">
            {{ superClean($task['title']) }}
        </h3>

        @if($task['can_delete'])
            <button
                wire:click="deleteTask({{ $task['id'] }})"
                class="opacity-0 group-hover:opacity-100 min-w-[44px] min-h-[44px] -m-2 p-2 rounded-xl text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                title="حذف"
            >
                <span class="material-symbols-rounded text-xl">delete</span>
            </button>
        @endif

        @if($task['can_change_status'])
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="opacity-0 group-hover:opacity-100 min-w-[32px] min-h-[32px] p-1 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 flex items-center justify-center"
                    title="محول کردن"
                >
                    <span class="material-symbols-rounded text-lg">person_add</span>
                </button>
                <div
                    x-show="open"
                    x-transition
                    class="absolute left-0 mt-2 w-48 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg z-50 overflow-hidden"
                    style="display: none;"
                >
                    <div class="p-2 border-b border-[var(--md-sys-color-outline-variant)]/40 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                        محول کردن به:
                    </div>
                    <ul class="max-h-40 overflow-y-auto custom-scrollbar">
                        <li>
                            <button
                                wire:click="assignTask({{ $task['id'] }}, null)"
                                @click="open = false"
                                class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors"
                            >
                                {{ $task['delegator_name'] ?? 'بدون مسئول' }}
                            </button>
                        </li>
                        @foreach($staffMembers as $staff)
                            <li>
                                <button
                                    wire:click="assignTask({{ $task['id'] }}, {{ $staff['id'] }})"
                                    @click="open = false"
                                    class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors {{ $task['assigned_to'] === $staff['id'] ? 'bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-primary)] font-bold' : '' }}"
                                >
                                    {{ $staff['full_name'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    @if(!empty($task['description']) && $density !== 'compact')
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed">
            {{ superClean($task['description']) }}
        </p>
    @endif

    @if($task['deadline'] || ($activeTab === 'my-tasks' && $task['assignee_name'] && $task['user_id'] !== auth()->id()) || ($activeTab === 'assigned-tasks' && $task['assignee_name']))
        <div class="flex flex-wrap items-center gap-2">
            @if($task['deadline'])
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold
                    {{ isPast($task['deadline']) && $task['status'] !== 'done'
                        ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'
                        : 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' }}"
                >
                    <span class="material-symbols-rounded text-sm">schedule</span>
                    <span class="tracking-wide">{{ $task['deadline_formatted'] }}</span>
                </div>
            @endif

            @if($activeTab === 'my-tasks')
                @if($task['assignee_name'] && $task['user_id'] !== auth()->id())
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-sm">arrow_downward</span>
                        <span>از: {{ $task['delegator_name'] }}</span>
                    </div>
                @endif
            @endif

            @if($activeTab === 'assigned-tasks')
                @if($task['assignee_name'])
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                        <span class="material-symbols-rounded text-sm">arrow_upward</span>
                        <span>به: {{ $task['assignee_name'] }}</span>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
        <div class="flex items-center gap-2">
            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 font-medium tracking-wider uppercase">
                #{{ $task['id'] }} • {{ $task['created_formatted'] }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border">
                {{ $colState['title'] }}
            </span>
        </div>

        <div class="flex items-center gap-1">
            @if($task['is_delegator'] && $column !== 'done')
                <button
                    wire:click="undoAssignment({{ $task['id'] }})"
                    class="flex items-center gap-1.5 min-h-[44px] px-3 py-2 rounded-xl text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95"
                >
                    <span class="material-symbols-rounded text-base">undo</span>
                    <span class="hidden sm:inline">لغو واگذاری</span>
                </button>
            @endif

            @if($task['can_change_status'])
                <button
                    wire:click="editTask({{ $task['id'] }})"
                    class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="ویرایش"
                >
                    <span class="material-symbols-rounded text-xl">edit</span>
                </button>
            @elseif($task['is_delegator'])
                <button
                    wire:click="viewTask({{ $task['id'] }})"
                    class="min-w-[44px] min-h-[44px] p-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="مشاهده"
                >
                    <span class="material-symbols-rounded text-xl">visibility</span>
                </button>
            @endif
        </div>
    </div>
</div>
