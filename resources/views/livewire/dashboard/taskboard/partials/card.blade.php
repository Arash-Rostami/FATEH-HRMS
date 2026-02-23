<div
    draggable="true"
    @dragstart="handleDragStart($event, {{ $task['id'] }})"
    @dragend="handleDragEnd($event)"
    class="group relative flex flex-col gap-2 p-4 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/10 hover:border-[var(--md-sys-color-primary)] hover:-translate-y-1 transition-all duration-300 cursor-grab active:cursor-grabbing select-none"
    dir="rtl"
>
    <!-- Priority/Type Indicator Bar -->
    <div class="absolute left-0 top-4 bottom-4 w-1 rounded-r-full
        {{ $task['status'] === 'done' ? 'bg-emerald-500' : ($task['status'] === 'in-progress' ? 'bg-amber-500' : 'bg-rose-500') }}
        opacity-50 group-hover:opacity-100 transition-opacity"></div>

    <!-- Header -->
    <div class="flex items-start justify-between gap-3 pl-2">
        <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2">
            {{ $task['title'] }}
        </h3>

        @if($task['can_delete'])
            <button
                wire:click="deleteTask({{ $task['id'] }})"
                class="opacity-0 group-hover:opacity-100 p-1.5 rounded-full text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-all duration-200"
                title="حذف"
            >
                <span class="material-symbols-rounded text-lg">delete</span>
            </button>
        @endif
    </div>

    <!-- Description -->
    @if(!empty($task['description']))
        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed opacity-80">
            {{ Str::limit(strip_tags($task['description']), 100) }}
        </p>
    @endif

    <!-- Metadata Tags -->
    <div class="flex flex-wrap items-center gap-2 mt-2">
        <!-- Deadline -->
        @if($task['deadline'])
            <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-medium border
                {{ \Carbon\Carbon::parse($task['deadline'])->isPast() && $task['status'] !== 'done'
                    ? 'bg-rose-50 text-rose-600 border-rose-200'
                    : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] border-transparent' }}"
            >
                <span class="material-symbols-rounded text-sm">schedule</span>
                <span>{{ $task['deadline_formatted'] }}</span>
            </div>
        @endif

        <!-- Assignee/Delegator Badge -->
        @if($activeTab === 'my-tasks')
            @if($task['assignee_name'] && $task['user_id'] !== auth()->id())
                <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <span class="material-symbols-rounded text-sm">download</span>
                    <span>از: {{ $task['delegator_name'] }}</span>
                </div>
            @endif
        @elseif($activeTab === 'assigned-tasks')
            @if($task['assignee_name'])
                <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold bg-violet-50 text-violet-600 border border-violet-100">
                    <span class="material-symbols-rounded text-sm">upload</span>
                    <span>به: {{ $task['assignee_name'] }}</span>
                </div>
            @endif
        @endif
    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-between mt-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/50">
        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]/60 font-medium tracking-wide">
            #{{ $task['id'] }} • {{ $task['created_formatted'] }}
        </span>

        @if($task['is_delegator'] && $column !== 'done')
            <button
                wire:click="undoAssignment({{ $task['id'] }})"
                class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-sm">undo</span>
                <span>لغو واگذاری</span>
            </button>
        @endif

        @if($task['can_change_status'])
             <button
                wire:click="editTask({{ $task['id'] }})"
                class="p-1.5 rounded-full text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200"
                title="ویرایش"
            >
                <span class="material-symbols-rounded text-lg">edit</span>
            </button>
        @endif
    </div>
</div>
