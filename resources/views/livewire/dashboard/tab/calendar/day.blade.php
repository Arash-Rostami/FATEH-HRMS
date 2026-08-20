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
@endphp

<div class="w-full flex flex-col gap-2">
    <div class="grid gap-1" style="grid-template-columns: 3rem minmax(0, 1fr)">
        <div></div>
        <div class="flex flex-col items-center py-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
            <span @class([
                'text-[11px] font-bold',
                'text-[var(--md-sys-color-error)]' => $meta['isFriday'],
                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_80%,transparent)]' => !$meta['isFriday'],
            ])>{{ $meta['weekLabel'] }}</span>
            <span @class([
                'text-[14px] font-black mt-0.5',
                'text-[var(--md-sys-color-primary)]' => $meta['isToday'],
                'text-[var(--md-sys-color-on-surface)]' => !$meta['isToday'],
            ])>{{ $meta['dayNum'] }}</span>
        </div>
    </div>

    <div class="grid gap-1" style="grid-template-columns: 3rem minmax(0, 1fr)">
        <div></div>
        <div class="min-h-[40px] flex flex-col gap-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] px-1 py-1">
            @foreach($meta['dayAllDay'] as $entry)
                @include('livewire.dashboard.tab.calendar.all-day-chip', ['entry' => $entry, 'keyPrefix' => 'day-allday', 'iconByType' => $iconByType])
            @endforeach
        </div>
    </div>

    @include('livewire.dashboard.tab.calendar.reservation-banner', ['reservations' => $reservations, 'columnsStyle' => 'grid-template-columns: 3rem minmax(0, 1fr)', 'gridSpan' => null, 'keyPrefix' => 'day-res'])

    <div class="flex">
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
                class="relative border-l border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] h-full"
                data-date="{{ $meta['jKey'] }}"
                data-hour-height="{{ $hourHeight }}"
            >
                @foreach($hourOffsets as $offset)
                    <div class="absolute left-0 right-0 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]" style="top: {{ $offset }}px"></div>
                @endforeach

                @foreach($meta['dayPills'] as $pill)
                    @php
                        if (!empty($pill['is_reservation_linked']) || !isset($pill['top'], $pill['height'], $pill['left_pct'], $pill['width_pct'])) {
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