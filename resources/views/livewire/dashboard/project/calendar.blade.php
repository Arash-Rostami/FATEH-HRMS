@php ($cal = $this->calendarDays)
@php ($presenter = new \App\Livewire\Dashboard\Project\Presentation\ProjectPresenter())
@php ($legend = $presenter->lifecycleLegend())
@php ($calModes = [
    ['value' => 'grid', 'icon' => 'grid_view', 'title' => 'نمای شبکه‌ای'],
    ['value' => 'list', 'icon' => 'view_list', 'title' => 'نمای فهرستی'],
    ['value' => 'gantt', 'icon' => 'view_timeline', 'title' => 'نمای گانت'],
])

<div class="w-full h-full flex flex-col gap-5 md:gap-6 bg-transparent" wire:key="calendar-{{ $activeProjectId }}" x-data="{ calView: 'grid' }" @project-calendar-refresh.window="$wire.refreshCalendar()">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 px-1 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="prevMonth" class="ripple-effect w-9 h-9 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                <span class="material-symbols-rounded text-xl">chevron_right</span>
            </button>
            <div class="w-28 md:w-32 text-center text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                {{ $cal['label'] }}
            </div>
            <button type="button" wire:click="nextMonth" class="ripple-effect w-9 h-9 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                <span class="material-symbols-rounded text-xl">chevron_left</span>
            </button>
            <div class="w-px h-5 md:h-6 bg-[var(--md-sys-color-outline-variant)]/30 mx-1 md:mx-2"></div>
            <button type="button" wire:click="calendarToday" class="ripple-effect h-9 px-3 rounded-lg text-xs font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,var(--md-sys-color-primary-container))] transition-all shadow-sm">
                امروز
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-1.5 md:gap-2 px-2">
            @foreach ($legend as $item)
                <div title="{{ $item['label'] }}" class="flex items-center gap-1 md:gap-1.5 px-2 md:px-2.5 py-1 md:py-1.5 rounded-lg border border-[var(--md-sys-color-outline-variant)] cursor-help transition-colors {{ $item['chipClass'] }}">
                    <span class="material-symbols-rounded text-[11px] md:text-[13px] {{ $item['iconColorClass'] }}">{{ $item['icon'] }}</span>
                    <span class="text-[9px] md:text-[10px] font-medium hidden lg:block">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>

        <x-ui.buttons.view-toggle :modes="$calModes" state="calView" action="" responsive />
    </div>

    @if (!empty($this->overdueCarry))
        <div wire:key="calendar-overdue-carry-{{ $activeProjectId }}" class="mx-1 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-error)_7%,transparent)] px-4 py-3 slide-up" style="animation-delay: 0.08s">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-error)]">error</span>
                <span class="text-xs font-bold text-[var(--md-sys-color-error)]">دیرکردهای پیش از این ماه</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->overdueCarry as $carry)
                    @php ($carryDate = $presenter->carryDateText($carry))
                    <button type="button" wire:key="carry-{{ $carry['task_id'] }}"
                            x-on:click="Livewire.dispatch('project-open-task', { taskId: {{ $carry['task_id'] }} })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_60%,transparent)] text-[var(--md-sys-color-on-error-container)] px-2.5 py-1.5 text-[10px] md:text-[11px] font-semibold hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_18%,transparent)] transition-colors max-w-full">
                        <span class="material-symbols-rounded text-[13px]">schedule</span>
                        <span class="truncate max-w-[18ch]" title="{{ $carry['title'] }}">{{ $carry['title'] }}</span>
                        <span class="opacity-70 whitespace-nowrap" dir="rtl">{{ $carryDate }}</span>
                        @if ($carrySlip = $presenter->slipText($carry))
                            <span class="shrink-0 rounded px-1 bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)] text-[9px] font-bold">{{ $carrySlip }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div wire:key="calendar-grid-pane-{{ $activeProjectId }}" x-show="calView === 'grid'" class="flex flex-col gap-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="w-full flex flex-col px-1 md:px-2">
            <div class="grid grid-cols-7 mb-2 pb-2 border-b border-[var(--md-sys-color-outline-variant)]/20 slide-up" style="animation-delay: 0.1s">
                @foreach ($cal['weekLabels'] as $i => $label)
                    <div class="flex items-center justify-center py-2">
                        <span @class([
                            'text-[10px] md:text-[11px] font-bold tracking-wider',
                            'text-[var(--md-sys-color-error)]' => $i === 6,
                            'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' => $i !== 6,
                        ])>
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1.5 md:gap-2 flex-1 content-start py-2">
                @foreach ($cal['days'] as $i => $day)
                    @if ($day === null)
                        <div class="aspect-[1/0.85] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_30%,transparent)] rounded-[14px]"></div>
                    @else
                        @php ($isOverdue = $day['hasOpenDeadline'] && !$day['isToday'] && $day['date'] < $cal['today'])
                        <button type="button" wire:click="selectCalendarDay('{{ $day['date'] }}')" wire:key="day-{{ $day['date'] }}"
                                @class([
                                    'group relative aspect-[1/0.85] w-full rounded-[14px] transition-all duration-300 ease-out flex flex-col items-center justify-start p-1.5 md:p-0 md:pt-1.5 gap-0.5 overflow-hidden isolate outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] slide-up',

                                    'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_10px_28px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)] dark:shadow-[0_10px_28px_rgba(0,0,0,0.6)] z-20 font-bold ring-1 ring-[var(--md-sys-color-primary)]' => $day['isSelected'],

                                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_8px_20px_rgba(0,0,0,0.5)] z-10 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_80%,transparent)]' => $day['isToday'] && !$day['isSelected'],

                                    'bg-[color-mix(in_srgb,var(--md-sys-color-error)_8%,transparent)] text-[var(--md-sys-color-error)] shadow-[0_6px_16px_color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)] dark:shadow-[0_6px_16px_rgba(0,0,0,0.4)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_16%,transparent)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-error)_25%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] hover:ring-[color-mix(in_srgb,var(--md-sys-color-error)_50%,transparent)]' => ($isOverdue || $i % 7 === 6) && !$day['isSelected'] && !$day['isToday'],

                                    'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] shadow-[0_6px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] dark:shadow-[0_6px_16px_rgba(0,0,0,0.4)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' => !($day['isSelected'] || $day['isToday'] || $isOverdue || $i % 7 === 6),
                                ])
                                style="animation-delay: {{ 0.15 + (floor($i / 7) * 0.04) }}s">

                            <span class="text-[10px] md:text-xs font-bold z-10 transition-colors {{ $day['isToday'] && !$day['isSelected'] ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                                {{ convertToPersian($day['day']) }}
                            </span>

                            <div class="flex items-center justify-center flex-wrap gap-0.5 sm:gap-1 min-h-[11px] sm:min-h-[13px] md:min-h-[16px]">
                                @if ($day['hasStart'])
                                    <span class="material-symbols-rounded text-[10px] sm:text-[12px] md:text-[15px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : $legend['start']['iconColorClass'] }} drop-shadow-sm">{{ $legend['start']['icon'] }}</span>
                                @endif
                                @if ($day['hasChange'])
                                    <span class="material-symbols-rounded text-[10px] sm:text-[12px] md:text-[15px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : $legend['change']['iconColorClass'] }} drop-shadow-sm">{{ $legend['change']['icon'] }}</span>
                                @endif
                                @if ($day['hasCompleted'])
                                    <span class="material-symbols-rounded text-[10px] sm:text-[12px] md:text-[15px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : $legend['completed']['iconColorClass'] }} drop-shadow-sm">{{ $legend['completed']['icon'] }}</span>
                                @endif
                                @if ($day['hasProjectDeadline'])
                                    <span class="material-symbols-rounded text-[10px] sm:text-[12px] md:text-[15px] {{ $day['isSelected'] ? 'text-[var(--md-sys-color-on-primary)]' : $legend['projectDeadline']['iconColorClass'] }} drop-shadow-sm">{{ $legend['projectDeadline']['icon'] }}</span>
                                @endif
                                @if ($day['hasDeadline'])
                                    <div class="flex items-center gap-[1px]">
                                        <span @class([
                                            'material-symbols-rounded text-[10px] sm:text-[12px] md:text-[15px] drop-shadow-sm',
                                            'text-[var(--md-sys-color-on-primary)]' => $day['isSelected'],
                                            'text-[var(--md-sys-color-error)] font-fill' => $isOverdue && !$day['isSelected'],
                                            $legend['deadline']['iconColorClass'] => !$isOverdue && $day['hasOpenDeadline'] && !$day['isSelected'],
                                            'text-[var(--md-sys-color-outline)]' => !$day['hasOpenDeadline'] && !$day['isSelected'],
                                        ])>{{ $legend['deadline']['icon'] }}</span>
                                        @if ($day['deadlineCount'] > 1)
                                            <span class="text-[7px] sm:text-[8px] md:text-[9.5px] font-bold tabular-nums leading-none {{ $day['isSelected'] ? 'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_90%,transparent)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">+{{ convertToPersian($day['deadlineCount'] - 1) }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if ($day['isToday'] && !$day['isSelected'] && !$day['hasStart'] && !$day['hasChange'] && !$day['hasCompleted'] && !$day['hasDeadline'] && !$day['hasProjectDeadline'])
                                <div class="absolute bottom-1.5 md:bottom-2 w-1 h-1 rounded-full bg-[var(--md-sys-color-primary)] opacity-50"></div>
                            @endif

                            @if ($day['isSelected'])
                                <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent pointer-events-none mix-blend-overlay rounded-[inherit]"></div>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_80%,transparent)] backdrop-blur-xl shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] overflow-hidden">
            <div class="px-5 md:px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-lowest)_40%,transparent)]">
                <h3 class="text-xs md:text-sm font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[18px] md:text-[20px] text-[var(--md-sys-color-primary)]">list_alt</span>
                    رویدادهای روز انتخاب شده
                </h3>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full text-sm text-right">
                    <thead class="text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-[9px] md:text-[10px] tracking-wider border-b border-[var(--md-sys-color-outline-variant)]/20">
                    <tr>
                        <th class="px-5 md:px-6 py-3 md:py-4">رویداد</th>
                        <th class="px-5 md:px-6 py-3 md:py-4">وظیفه</th>
                        <th class="px-5 md:px-6 py-3 md:py-4 hidden md:table-cell">جزئیات</th>
                        <th class="px-5 md:px-6 py-3 md:py-4">زمان</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/10">
                    @forelse ($this->selectedDayTimeline as $event)
                        @php ($d = $presenter->lifecycleEventData($event))
                        <tr wire:key="timeline-{{ $event['marker'] }}-{{ $event['task_id'] }}-{{ $event['time'] }}"
                            x-on:click="Livewire.dispatch('project-open-task', { taskId: {{ $event['task_id'] }} })" dir="auto"
                            class="hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] transition-colors duration-200 cursor-pointer group">
                            <td class="px-5 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 md:gap-1.5 px-2 md:px-2.5 py-1 md:py-1.5 rounded-lg border border-[var(--md-sys-color-outline-variant)] {{ $d['chipClass'] }}">
                                        <span class="material-symbols-rounded text-[11px] md:text-[13px] {{ $d['iconColorClass'] }}">{{ $d['icon'] }}</span>
                                        <span class="text-[9px] md:text-[10px] font-medium">{{ $d['markerLabel'] }}</span>
                                    </span>
                            </td>
                            <td class="px-5 md:px-6 py-3 md:py-4">
                                <div class="flex flex-col gap-0.5 md:gap-1 max-w-[20ch]">
                                    <span class="text-[9px] md:text-[10px] font-bold tracking-wide text-[var(--md-sys-color-on-surface-variant)]" title="{{ $d['badge'] }}">{{ $d['badge'] }}</span>
                                    <span class="text-[11px] md:text-xs font-semibold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors line-clamp-1" title="{{ $d['title'] }}">{{ $d['title'] }}</span>
                                </div>
                            </td>
                            <td class="px-5 md:px-6 py-3 md:py-4 hidden md:table-cell text-[11px] md:text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                <div class="line-clamp-1 max-w-[28ch]" title="{{ $d['line'] }}">
                                    {{ $d['line'] }}
                                    @if ($d['pausedText']) <span class="mx-1 text-[var(--md-sys-color-outline-variant)]">•</span> <span class="text-[var(--md-sys-color-tertiary)]">{{ $d['pausedText'] }} در انتظار</span> @endif
                                </div>
                            </td>
                            <td class="px-5 md:px-6 py-3 md:py-4 whitespace-nowrap text-[11px] md:text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">{{ $d['time'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10"><x-ui.empty icon="event_busy" title="رویدادی در این روز ثبت نشده" variant="list"/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div wire:key="calendar-list-pane-{{ $activeProjectId }}" x-show="calView === 'list'" class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] backdrop-blur-xl shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] overflow-hidden" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full text-sm text-right" dir="rtl">
                <thead class="text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-[9px] md:text-[10px] tracking-wider border-b border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-lowest)_40%,transparent)]">
                <tr>
                    <th class="px-5 md:px-6 py-3 md:py-4 text-center">روز</th>
                    <th class="px-5 md:px-6 py-3 md:py-4">رویداد</th>
                    <th class="px-5 md:px-6 py-3 md:py-4 text-center">وظیفه</th>
                    <th class="px-5 md:px-6 py-3 md:py-4 hidden md:table-cell text-center">جزئیات</th>
                    <th class="px-5 md:px-6 py-3 md:py-4">زمان</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/10">
                @forelse ($this->monthAgenda as $event)
                    @php ($d = $presenter->lifecycleEventData($event))
                    <tr wire:key="agenda-{{ $event['date'] }}-{{ $event['marker'] }}-{{ $event['task_id'] }}-{{ $event['time'] }}"
                        wire:click="selectCalendarDay('{{ $event['date'] }}')" dir="rtl"
                        class="hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] transition-colors duration-200 cursor-pointer group">
                        <td class="px-5 md:px-6 py-3 md:py-4 whitespace-nowrap align-middle">
                            <div class="flex items-center justify-center w-8 h-8 md:w-9 md:h-9 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] text-xs md:text-sm font-bold shadow-sm group-hover:scale-105 transition-transform">
                                {{ convertToPersian($event['day']) }}
                            </div>
                        </td>
                        <td class="px-5 md:px-6 py-3 md:py-4 whitespace-nowrap align-middle">
                            <span class="inline-flex items-center gap-1 md:gap-1.5 px-2 md:px-2.5 py-1 md:py-1.5 rounded-lg border border-[var(--md-sys-color-outline-variant)] {{ $d['chipClass'] }}">
                                <span class="material-symbols-rounded text-[11px] md:text-[13px] {{ $d['iconColorClass'] }}">{{ $d['icon'] }}</span>
                                <span class="text-[9px] md:text-[10px] font-medium">{{ $d['markerLabel'] }}</span>
                            </span>
                        </td>
                        <td class="px-5 md:px-6 py-3 md:py-4 align-middle text-center">
                            <div class="flex flex-col items-center gap-0.5 md:gap-1 max-w-[20ch]">
                                <span class="text-[9px] md:text-[10px] font-bold tracking-wide text-[var(--md-sys-color-on-surface-variant)]" title="{{ $d['badge'] }}">{{ $d['badge'] }}</span>
                                <span class="text-[11px] md:text-xs font-semibold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors line-clamp-1" title="{{ $d['title'] }}">{{ $d['title'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 md:px-6 py-3 md:py-4 hidden md:table-cell text-[11px] md:text-xs text-[var(--md-sys-color-on-surface-variant)] align-middle">
                            <div class="flex justify-center">
                                <div class="line-clamp-1 max-w-[28ch] text-center" title="{{ $d['line'] }}">{{ $d['line'] }}</div>
                            </div>
                        </td>
                        <td class="px-5 md:px-6 py-3 md:py-4 whitespace-nowrap text-[11px] md:text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] align-middle" dir="ltr">{{ $d['time'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10"><x-ui.empty icon="calendar_month" title="رویدادی در این ماه ثبت نشده" variant="list"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:key="calendar-gantt-pane-{{ $activeProjectId }}" x-show="calView === 'gantt'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
        @include('livewire.dashboard.project.calendar-gantt')
    </div>
</div>
