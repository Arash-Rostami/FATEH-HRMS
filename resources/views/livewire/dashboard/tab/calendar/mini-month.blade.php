@php
    $mm = $presenter->miniMonthData($this->miniMonthDate);
    $weekDays = $mm['weekDays'];
    $monthLabel = $mm['monthLabel'];
    $yearLabel = $mm['yearLabel'];
    $prevMonth = $mm['prevMonth'];
    $nextMonth = $mm['nextMonth'];
    $prevYear = $mm['prevYear'];
    $nextYear = $mm['nextYear'];
@endphp

<div class="w-full flex flex-col gap-1.5 p-3">
    <div class="flex items-center justify-between">
        <button
            type="button"
            wire:click="stepMiniMonth(-12)"
            title="{{ convertToPersian($prevYear) }}"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
        >
            <span class="material-symbols-rounded text-[16px]">keyboard_double_arrow_right</span>
        </button>
        <span class="text-xs font-black text-[var(--md-sys-color-on-surface)] tabular-nums">{{ convertToPersian($yearLabel) }}</span>
        <button
            type="button"
            wire:click="stepMiniMonth(12)"
            title="{{ convertToPersian($nextYear) }}"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
        >
            <span class="material-symbols-rounded text-[16px]">keyboard_double_arrow_left</span>
        </button>
    </div>

    <div class="flex items-center justify-between">
        <button
            type="button"
            wire:click="stepMiniMonth(-1)"
            title="{{ convertToPersian($prevMonth) }}"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
        >
            <span class="material-symbols-rounded text-[18px]">chevron_right</span>
        </button>
        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($monthLabel) }}</span>
        <button
            type="button"
            wire:click="stepMiniMonth(1)"
            title="{{ convertToPersian($nextMonth) }}"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
        >
            <span class="material-symbols-rounded text-[18px]">chevron_left</span>
        </button>
    </div>

    <div class="grid grid-cols-7 gap-0.5">
        @foreach($weekDays as $i => $dayName)
            <div class="flex items-center justify-center py-1">
                <span @class([
                    'text-[9px] font-bold',
                    'text-[var(--md-sys-color-error)]' => $i === 6,
                    'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]' => $i !== 6,
                ])>{{ $dayName }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-0.5">
        @foreach($this->miniMonthDays as $index => $day)
            @if($day === null)
                <div wire:key="mini-empty-{{ $index }}"></div>
            @else
                <button
                    type="button"
                    wire:key="mini-day-{{ $day['date'] }}"
                    wire:click="goToDate('{{ $day['date'] }}')"
                    @click="pickerOpen = false"
                    @class([
                        'aspect-square w-full rounded-md flex items-center justify-center text-[10px] font-bold transition-all duration-200',
                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $day['isSelected'],
                        'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] text-[var(--md-sys-color-primary)]' => $day['isToday'] && !$day['isSelected'],
                        'text-[var(--md-sys-color-error)]' => ($index % 7 === 6 || $day['hasHoliday']) && !$day['isSelected'] && !$day['isToday'],
                        'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]' => !($day['isSelected'] || $day['isToday'] || $index % 7 === 6 || $day['hasHoliday']),
                    ])
                >
                    {{ convertToPersian($day['day']) }}
                </button>
            @endif
        @endforeach
    </div>
</div>