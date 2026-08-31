@php($id = $type === 'project' ? $row['project_id'] : 'standalone')
@php($group = $type === 'project' ? 'projects' : 'standalone')

<div class="flex flex-col gap-2" wire:key="tasksheet-row-{{ $type }}-{{ $id }}">
    <div class="flex flex-wrap items-center gap-3 rounded-xl bg-[var(--md-sys-color-surface-container-low)] px-4 py-3 transition-colors hover:bg-[var(--md-sys-color-primary)]/[0.03]">
        @unless($readOnly)
            <button type="button" @click="toggleExpanded(@js($group), @js($id))"
                    class="flex items-center justify-center w-7 h-7 rounded-lg hover:bg-[var(--md-sys-color-primary-container)]/40 transition-colors print:hidden shrink-0">
                <span class="material-symbols-rounded text-[18px] transition-transform duration-200" :class="isExpanded(@js($group), @js($id)) ? 'rotate-90' : ''">chevron_left</span>
            </button>
        @endunless

        <div class="flex-1 min-w-0 flex flex-wrap items-center gap-2">
            @if($type === 'project')
                @php($badge = $presenter->roleBadge($row['role']))
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $badge['classes'] }}">
                    <span class="material-symbols-rounded text-[12px]">{{ $badge['icon'] }}</span>
                    {{ $badge['label'] }}
                </span>
                <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)] truncate" dir="auto">{{ superClean($row['project_name'] ?? '') }}</span>
                @if($row['is_archived'])
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60">(آرشیو شده)</span>
                @endif
            @else
                <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">وظایف مستقل</span>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs text-[var(--md-sys-color-on-surface-variant)]">
            <span class="tabular-nums">{{ convertToPersian($row['completed']) }} تکمیل‌شده</span>
            <span class="inline-flex items-center gap-1 tabular-nums">
                {{ $row['on_time_percent'] !== null ? convertToPersian($row['on_time_percent']) . '٪ به‌موقع' : '—' }}
                @if($type === 'project')
                    @php($health = $presenter->projectHealthChip($row))
                    @if($health)
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-md {{ $health['classes'] }}" title="{{ $health['text'] }}">
                            <span class="material-symbols-rounded text-[11px]">{{ $health['icon'] }}</span>
                        </span>
                    @endif
                @endif
            </span>
            <span class="tabular-nums">{{ convertToPersian($row['still_overdue']) }} معوق</span>
            <span class="tabular-nums">{{ convertToPersian($row['in_progress']) }} در جریان</span>
        </div>

        @if($type === 'project' && !$readOnly)
            <button type="button" wire:click="scopeToProject({{ $row['project_id'] }})"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors print:hidden shrink-0">
                <span class="material-symbols-rounded text-[12px]">filter_center_focus</span>
                فقط این پروژه
            </button>
        @endif
    </div>

    <div x-show="isExpanded(@js($group), @js($id))" x-cloak x-collapse>
        @include('livewire.dashboard.tasksheet.task-table', ['tasks' => $row['tasks']])
    </div>
</div>
