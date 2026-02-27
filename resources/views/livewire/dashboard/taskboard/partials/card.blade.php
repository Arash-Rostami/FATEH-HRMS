<div draggable="true" @dragstart="handleDragStart($event, {{ $task['id'] }})" @dragend="handleDragEnd($event)" class="group relative flex flex-col gap-2 p-5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:-translate-y-1 transition-all duration-300 cursor-grab active:cursor-grabbing select-none overflow-hidden" dir="rtl">
    <div class="absolute left-0 top-0 bottom-0 w-1
        {{ $task['status'] === 'done' ? 'bg-[var(--md-sys-color-primary)]' : ($task['status'] === 'in-progress' ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-secondary)]') }}
        opacity-50 group-hover:opacity-100 transition-opacity"></div>
    <div class="flex items-start justify-between gap-3 pl-2">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2">{{ $task['title'] }}</h3>
        @if($task['can_delete'])
            <button wire:click="deleteTask({{ $task['id'] }})" class="opacity-0 group-hover:opacity-100 p-1.5 rounded-xl text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-all duration-200" title="حذف">
                <span class="material-symbols-rounded text-lg">delete</span>
            </button>
        @endif
    </div>
    @if(!empty($task['description']))
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 line-clamp-2 leading-relaxed opacity-80">{{ Str::limit(strip_tags($task['description']), 100) }}</p>
    @endif
    <div class="flex flex-wrap items-center gap-2 mt-2">
        @if($task['deadline'])
            <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-medium border
                {{ \Carbon\Carbon::parse($task['deadline'])->isPast() && $task['status'] !== 'done'
                    ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border-[var(--md-sys-color-error)]/30'
                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border-transparent' }}">
                <span class="material-symbols-rounded text-sm">schedule</span>
                <span>{{ $task['deadline_formatted'] }}</span>
            </div>
        @endif
        @if($activeTab === 'my-tasks')
            @if($task['assignee_name'] && $task['user_id'] !== auth()->id())
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-secondary)]/20">
                    <span class="material-symbols-rounded text-sm">download</span>
                    <span>از: {{ $task['delegator_name'] }}</span>
                </div>
            @endif
        @elseif($activeTab === 'assigned-tasks')
            @if($task['assignee_name'])
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border border-[var(--md-sys-color-tertiary)]/20">
                    <span class="material-symbols-rounded text-sm">upload</span>
                    <span>به: {{ $task['assignee_name'] }}</span>
                </div>
            @endif
        @endif
    </div>
    <div class="flex items-center justify-between mt-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/50">
        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]/60 font-medium tracking-wide">#{{ $task['id'] }} • {{ $task['created_formatted'] }}</span>
        @if($task['is_delegator'] && $column !== 'done')
            <button wire:click="undoAssignment({{ $task['id'] }})" class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                <span class="material-symbols-rounded text-sm">undo</span>
                <span>لغو واگذاری</span>
            </button>
        @endif
        @if($task['can_change_status'])
            <button wire:click="editTask({{ $task['id'] }})" class="p-1.5 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200" title="ویرایش">
                <span class="material-symbols-rounded text-lg">edit</span>
            </button>
        @endif
    </div>
</div>
