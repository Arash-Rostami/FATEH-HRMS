@php
    $mm = $presenter->miniMonthData($this->miniMonthDate);
    $weekDays = $mm['weekDays'];
    $miniLabel = $mm['miniLabel'];
    $prevMonth = $mm['prevMonth'];
    $nextMonth = $mm['nextMonth'];
@endphp

<div class="w-full flex flex-col gap-2 p-2 bg-transparent">
    <div class="flex items-center justify-between px-1">
        <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($miniLabel) }}</span>
        <div class="flex items-center gap-1">
            <button
                type="button"
                wire:click="$wire.set('miniMonthDate', '{{ $prevMonth }}')"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
            >
                <span class="material-symbols-rounded text-[18px]">chevron_right</span>
            </button>
            <button
                type="button"
                wire:click="$wire.set('miniMonthDate', '{{ $nextMonth }}')"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-colors"
            >
                <span class="material-symbols-rounded text-[18px]">chevron_left</span>
            </button>
        </div>
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
                    wire:click="selectDate('{{ $day['date'] }}')"
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