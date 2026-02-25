<div
    draggable="true"
    @dragstart="handleDragStart($event, {{ $task['id'] }})"
    @dragend="handleDragEnd($event)"
    class="group relative flex flex-col gap-3 p-4 md:p-5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:-translate-y-1 transition-all duration-300 cursor-grab active:cursor-grabbing active:scale-[0.98] select-none"
    dir="rtl"
>
    <!-- Status Indicator Stripe -->
    <div class="absolute left-0 top-0 h-[3px] rounded-tr-xl
        {{ $task['status'] === 'done' ? 'bg-[var(--md-sys-color-tertiary)]' : ($task['status'] === 'in-progress' ? 'bg-[var(--md-sys-color-secondary)]' : 'bg-[var(--md-sys-color-primary)]') }}
        shadow-[0_2px_8px_color-mix(in_srgb,{{ $task['status'] === 'done' ? 'var(--md-sys-color-tertiary)' : ($task['status'] === 'in-progress' ? 'var(--md-sys-color-secondary)' : 'var(--md-sys-color-primary)') }}_40%,transparent)]
        opacity-60 group-hover:opacity-100 transition-opacity w-16 md:w-20"></div>

    <!-- Header -->
    <div class="flex items-start justify-between gap-3">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2 flex-1">
            {{ $task['title'] }}
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
    </div>

    <!-- Description -->
    @if(!empty($task['description']))
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed">
            {{ Str::limit(strip_tags($task['description']), 100) }}
        </p>
    @endif

    <!-- Metadata Badges -->
    @if($task['deadline'] || ($activeTab === 'my-tasks' && $task['assignee_name'] && $task['user_id'] !== auth()->id()) || ($activeTab === 'assigned-tasks' && $task['assignee_name']))
        <div class="flex flex-wrap items-center gap-2">
            <!-- Deadline Badge -->
            @if($task['deadline'])
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold
                    {{ \Carbon\Carbon::parse($task['deadline'])->isPast() && $task['status'] !== 'done'
                        ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'
                        : 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' }}"
                >
                    <span class="material-symbols-rounded text-sm">schedule</span>
                    <span class="tracking-wide">{{ $task['deadline_formatted'] }}</span>
                </div>
            @endif

            <!-- Delegator Badge (My Tasks) -->
            @if($activeTab === 'my-tasks')
                @if($task['assignee_name'] && $task['user_id'] !== auth()->id())
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-sm">arrow_downward</span>
                        <span>از: {{ $task['delegator_name'] }}</span>
                    </div>
                @endif
            @endif

            <!-- Assignee Badge (Assigned Tasks) -->
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

    <!-- Footer Actions -->
    <div class="flex items-center justify-between gap-3 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 font-medium tracking-wider uppercase">
            #{{ $task['id'] }} • {{ $task['created_formatted'] }}
        </span>

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
            @endif
        </div>
    </div>
</div>
