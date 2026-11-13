@php
    $grid = $this->gridData('day');
    $meta = $grid['daysMeta'][0];
    $hourOffsets = $grid['hourOffsets'];
    $hourLabels = $grid['hourLabels'];
    $gridHeight = $grid['gridHeight'];
    $hourHeight = $grid['hourHeight'];
    $startHour = $grid['startHour'];
    $endHour = $grid['endHour'];
    $iconByType = $grid['iconByType'];
    $reservations = $grid['allReservations'];

    $allDayCount = count($meta['dayAllDay'] ?? []);
    $firstEventTop = collect($meta['dayPills'] ?? [])->pluck('top')->filter(fn ($top) => $top !== null)->min();
    $dayConfig = ['hourHeight' => (int) $hourHeight, 'startHour' => (int) $startHour, 'endHour' => (int) $endHour, 'isToday' => (bool) $meta['isToday'], 'firstTop' => $firstEventTop];
@endphp

<div
    wire:key="day-view-{{ $meta['jKey'] }}"
    x-data="calendarDay(@js($dayConfig))"
    class="w-full flex flex-col gap-2 select-none"
>
    {{-- Sticky Day Header with Material 3 Today Pill --}}
    <div class="grid gap-1" style="grid-template-columns: 3rem minmax(0, 1fr)">
        <div></div>
        <div class="flex flex-col items-center py-1.5 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
            <span @class([
                'text-[11px] font-bold',
                'text-[var(--md-sys-color-error)]' => $meta['isFriday'],
                'text-[var(--md-sys-color-primary)]' => $meta['isToday'] && !$meta['isFriday'],
                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' => !$meta['isFriday'] && !$meta['isToday'],
            ])>{{ $meta['weekLabel'] }}</span>
            <span @class([
                'text-[14px] font-black mt-0.5 inline-flex items-center justify-center transition-transform',
                'w-7 h-7 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-xs' => $meta['isToday'],
                'text-[var(--md-sys-color-on-surface)]' => !$meta['isToday'],
            ])>{{ $meta['dayNum'] }}</span>
        </div>
    </div>

    {{-- Smart Collapsible All-Day Row (Only takes space when entries exist) --}}
    @if($allDayCount > 0)
        <div class="grid gap-1" style="grid-template-columns: 3rem minmax(0, 1fr)">
            <div class="flex items-start justify-end pr-1 pt-2">
                <span class="text-[9px] font-bold text-[var(--md-sys-color-on-surface-variant)] opacity-60">تمام‌روز</span>
            </div>
            <div class="flex flex-col gap-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] px-1 py-1.5">
                @foreach($meta['dayAllDay'] as $idx => $entry)
                    <div x-show="{{ $idx < 3 ? 'true' : 'showAllDay' }}" x-cloak>
                        @include('livewire.dashboard.tab.calendar.all-day-chip', ['entry' => $entry, 'keyPrefix' => 'day-allday', 'iconByType' => $iconByType])
                    </div>
                @endforeach

                @if($allDayCount > 3)
                    <button
                        type="button"
                        @click="showAllDay = !showAllDay"
                        class="self-start text-[10px] font-bold text-[var(--md-sys-color-primary)] hover:underline px-1.5 py-0.5 rounded-md flex items-center gap-0.5"
                    >
                        <span x-text="showAllDay ? 'بستن' : '+ {{ convertToPersian($allDayCount - 3) }} مورد دیگر'"></span>
                        <span class="material-symbols-rounded text-[13px] transition-transform" :class="showAllDay ? 'rotate-180' : ''">expand_more</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    @include('livewire.dashboard.tab.calendar.reservation-banner', ['reservations' => $reservations, 'columnsStyle' => 'grid-template-columns: 3rem minmax(0, 1fr)', 'gridSpan' => null, 'keyPrefix' => 'day-res'])

    {{-- Main Timeline Grid --}}
    <div class="flex" x-ref="gridWrapper">
        <div class="w-12 shrink-0 relative" style="height: {{ $gridHeight }}px">
            @foreach($hourOffsets as $h => $offset)
                <div class="absolute left-0 right-0 -translate-y-1/2 text-[10px] font-bold text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)] tabular-nums pr-1 text-right" style="top: {{ $offset }}px">
                    {{ $hourLabels[$h] }}
                </div>
            @endforeach
        </div>

        <div class="flex-1 relative" style="height: {{ $gridHeight }}px">
            <div
                wire:key="day-col-{{ $meta['jKey'] }}"
                class="calendar-day-column relative border-l border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] h-full"
                data-date="{{ $meta['jKey'] }}"
                data-hour-height="{{ $hourHeight }}"
            >
                @foreach($hourOffsets as $offset)
                    {{-- Full Hour Line --}}
                    <div class="absolute left-0 right-0 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] pointer-events-none" style="top: {{ $offset }}px"></div>
                    {{-- Half-Hour Subtle Dashed Guide --}}
                    @if($offset + ($hourHeight / 2) < $gridHeight)
                        <div class="absolute left-0 right-0 border-t border-dashed border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_10%,transparent)] pointer-events-none" style="top: {{ $offset + ($hourHeight / 2) }}px"></div>
                    @endif
                @endforeach

                @foreach($meta['dayPills'] as $pill)
                    @php
                        if (!isset($pill['top'], $pill['height'], $pill['left_pct'], $pill['width_pct'])) {
                            continue;
                        }
                    @endphp
                    @include('livewire.dashboard.tab.calendar.event-block', ['event' => $pill])
                @endforeach

                @if($meta['isToday'] && $meta['startIso'])
                    @include('livewire.dashboard.tab.calendar.now-line', ['startIso' => $meta['startIso'], 'spanHours' => $endHour - $startHour])
                @endif
            </div>
        </div>
    </div>
</div>
