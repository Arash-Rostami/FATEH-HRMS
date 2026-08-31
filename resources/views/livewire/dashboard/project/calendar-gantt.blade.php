@php ($gantt = $this->ganttRows)
@php ($days = (int) $gantt['daysCount'])
@php ($cellPct = $days > 0 ? round(100 / $days, 4) : 0)

<div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] backdrop-blur-xl shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] overflow-hidden slide-up" style="animation-delay: 0.1s">
    <div class="flex items-center gap-2 px-5 md:px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-lowest)_40%,transparent)]">
        <span class="material-symbols-rounded text-[18px] md:text-[20px] text-[var(--md-sys-color-primary)]">view_timeline</span>
        <h3 class="text-xs md:text-sm font-bold text-[var(--md-sys-color-on-surface)]">خط زمانی وظایف</h3>
    </div>

    @if ($days === 0 || empty($gantt['rows']))
        <div class="py-10">
            <x-ui.empty icon="{{ $gantt['hasAnyTask'] ? 'event_busy' : 'inbox' }}"
                        title="{{ $gantt['hasAnyTask'] ? 'وظیفه‌ای در بازهٔ این ماه دیده نمی‌شود' : 'هنوز وظیفه‌ای ثبت نشده است' }}"
                        variant="list"/>
        </div>
    @else
        <div class="overflow-x-auto overflow-y-auto custom-scrollbar max-h-[32rem]">
            <div class="min-w-max">
                <div class="sticky top-0 z-30 flex bg-[var(--md-sys-color-surface)]/95 backdrop-blur-sm border-b border-[var(--md-sys-color-outline-variant)]/20">
                    <div class="sticky start-0 z-20 w-40 md:w-56 shrink-0 bg-[var(--md-sys-color-surface)] flex items-end px-4 pb-2">
                        <span class="text-[9px] md:text-[10px] font-bold tracking-wider text-[var(--md-sys-color-on-surface-variant)]">وظیفه</span>
                    </div>
                    <div class="relative flex-1 grid" style="grid-template-columns: repeat({{ $days }}, minmax(30px, 1fr))">
                        @foreach ($gantt['dayNumbers'] as $idx => $num)
                            <div wire:key="gantt-day-{{ $idx }}" @class([
                                'h-9 flex items-start justify-center pt-1.5 text-[10px] font-bold tabular-nums',
                                'text-[var(--md-sys-color-primary)]' => $idx === $gantt['todayIndex'],
                                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]' => $idx !== $gantt['todayIndex'],
                            ])>{{ convertToPersian($num) }}</div>
                        @endforeach
                    </div>
                </div>

                <div class="relative pb-3">
                    <div class="pointer-events-none absolute inset-0 z-0 flex items-stretch">
                        <div class="w-40 md:w-56 shrink-0"></div>
                        <div class="relative flex-1">
                            @if ($gantt['todayIndex'] !== null)
                                <div class="absolute inset-y-0 border-s-2 border-dashed border-[var(--md-sys-color-primary)]/40"
                                     style="inset-inline-start: {{ round((($gantt['todayIndex'] + 0.5) / $days) * 100, 2) }}%"></div>
                            @endif
                            @if ($gantt['projectDeadlineIndex'] !== null)
                                <div class="absolute inset-y-0 border-s-2 border-dotted border-[var(--md-sys-color-tertiary)]/60"
                                     style="inset-inline-start: {{ round((($gantt['projectDeadlineIndex'] + 0.5) / $days) * 100, 2) }}%"></div>
                            @endif
                        </div>
                    </div>

                    @foreach ($gantt['rows'] as $row)
                        @php ($d = $presenter->ganttRowData($row))
                        @php ($degenerate = $row['widthPct'] <= $cellPct + 0.01)
                        <div class="relative flex items-stretch border-b border-[var(--md-sys-color-outline-variant)]/10 last:border-b-0 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_3%,transparent)] transition-colors cursor-pointer group"
                             wire:key="gantt-row-{{ $row['task_id'] }}"
                             x-on:click="Livewire.dispatch('project-open-task', { taskId: {{ $row['task_id'] }} })">

                            <div class="sticky start-0 z-20 w-40 md:w-56 shrink-0 bg-[var(--md-sys-color-surface)] px-4 py-2.5 flex items-center gap-2 min-w-0">
                                <span class="text-[9px] md:text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] shrink-0" dir="ltr">#{{ convertToPersian($row['task_id']) }}</span>
                                <div class="flex flex-col min-w-0 flex-1">
                                    <span class="text-[11px] md:text-xs font-semibold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors line-clamp-1" title="{{ $row['title'] }}">{{ $row['title'] }}</span>
                                    <span class="text-[9px] md:text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($row['progressPct']) }}٪ @if ($row['isDone'])<span class="material-symbols-rounded text-[11px] align-middle text-[var(--md-sys-color-primary)]">task_alt</span>@endif</span>
                                </div>
                            </div>

                            <div class="relative flex-1"
                                 style="background-image: repeating-linear-gradient(to left, color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent) 0 1px, transparent 1px {{ $cellPct }}%);">

                                <div class="absolute inset-y-0 flex items-center" style="inset-inline-start: {{ $row['startPct'] }}%; width: {{ $row['widthPct'] }}%">
                                    @if ($degenerate)
                                        <div class="mx-auto w-3 h-3 rotate-45 rounded-[3px] {{ $d['barClass'] }} shadow-sm" title="{{ $d['titleAttr'] }}"></div>
                                    @else
                                        <div class="relative h-5 w-full rounded-lg flex items-center px-1.5 overflow-hidden shadow-sm {{ $d['barClass'] }}
                                            @if ($row['rightClipped'] && !$row['isDone']) [border-inline-end:2px_dashed_color-mix(in_srgb,var(--md-sys-color-primary)_55%,transparent)] @endif"
                                            title="{{ $d['titleAttr'] }}">
                                            <div class="absolute inset-y-0 start-0 rounded-s-lg {{ $d['fillClass'] }}" style="width: {{ $row['progressPct'] }}%"></div>
                                            @if ($d['slipText'])
                                                <span class="relative z-10 ms-auto shrink-0 inline-flex items-center rounded-md px-1.5 py-0.5 bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)] text-[8px] md:text-[9px] font-bold max-w-[9rem] truncate" title="{{ $d['slipText'] }}">{{ $d['slipText'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if ($row['tailStartPct'] !== null)
                                    <div class="absolute top-1/2 -translate-y-1/2 h-3 rounded-e-md bg-[color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)]"
                                         style="inset-inline-start: {{ $row['tailStartPct'] }}%; width: {{ $row['tailWidthPct'] }}%"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>