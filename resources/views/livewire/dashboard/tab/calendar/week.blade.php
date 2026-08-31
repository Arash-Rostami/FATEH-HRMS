@php
    $grid = $this->gridData('week');
    $daysMeta = $grid['daysMeta'];
    $hourOffsets = $grid['hourOffsets'];
    $hourLabels = $grid['hourLabels'];
    $gridHeight = $grid['gridHeight'];
    $hourHeight = $grid['hourHeight'];
    $startHour = $grid['startHour'];
    $endHour = $grid['endHour'];
    $iconByType = $grid['iconByType'];
    $reservations = $grid['allReservations'];
    $spanningReservations = $grid['spanningReservations'] ?? [];
@endphp

<div class="w-full flex flex-col gap-2">
    <div class="grid gap-1" style="grid-template-columns: 3rem repeat(7, minmax(0, 1fr))">
        <div></div>
        @foreach($daysMeta as $meta)
            <div wire:key="week-head-{{ $meta['jKey'] }}" x-show="@js(!$meta['isFriday']) || !$wire.hideFriday" x-cloak class="flex flex-col items-center py-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
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
        @endforeach
    </div>

    <div class="grid gap-1" style="grid-template-columns: 3rem repeat(7, minmax(0, 1fr))">
        <div></div>
        @foreach($daysMeta as $meta)
            <div wire:key="week-allday-{{ $meta['jKey'] }}" x-show="@js(!$meta['isFriday']) || !$wire.hideFriday" x-cloak class="min-h-[40px] flex flex-col gap-1 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] px-1 py-1">
                @foreach($meta['dayAllDay'] as $entry)
                    @include('livewire.dashboard.tab.calendar.all-day-chip', ['entry' => $entry, 'keyPrefix' => 'allday', 'iconByType' => $iconByType])
                @endforeach
            </div>
        @endforeach
    </div>

    @include('livewire.dashboard.tab.calendar.reservation-banner', ['reservations' => $reservations, 'columnsStyle' => 'grid-template-columns: 3rem repeat(7, minmax(0, 1fr))', 'gridSpan' => 'grid-column: 2 / span 7', 'keyPrefix' => 'week-res'])

    @if(!empty($spanningReservations))
        <div class="grid gap-1" style="grid-template-columns: 3rem repeat(7, minmax(0, 1fr))">
            <div></div>
            @foreach($spanningReservations as $s)
                <div
                    wire:key="week-span-{{ $s['id'] }}"
                    @click="$wire.reservationHint()"
                    class="flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold shadow-sm cursor-pointer select-none bg-[color-mix(in_srgb,var(--tool-sage-bg,var(--md-sys-color-tertiary-container))_70%,transparent)] text-[var(--tool-sage-color,var(--md-sys-color-on-tertiary-container))] border border-[color-mix(in_srgb,var(--tool-sage-color,var(--md-sys-color-tertiary))_40%,transparent)]"
                    style="grid-column: {{ 2 + $s['col_start'] }} / span {{ $s['col_span'] }}"
                    title="{{ $s['title'] }}"
                >
                    <span class="material-symbols-rounded text-[14px] shrink-0" style="font-variation-settings: 'FILL' 1;">event_seat</span>
                    <span class="truncate flex-1">{{ $s['title'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex">
        <div class="w-12 shrink-0 relative" style="height: {{ $gridHeight }}px">
            @foreach($hourOffsets as $h => $offset)
                <div class="absolute left-0 right-0 -translate-y-1/2 text-[10px] font-bold text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)] tabular-nums pr-1 text-right" style="top: {{ $offset }}px">
                    {{ $hourLabels[$h] }}
                </div>
            @endforeach
        </div>

        <div class="flex-1 grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr))">
            @foreach($daysMeta as $meta)
                <div
                    wire:key="week-col-{{ $meta['jKey'] }}"
                    x-show="@js(!$meta['isFriday']) || !$wire.hideFriday"
                    x-cloak
                    class="calendar-day-column relative border-l border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"
                    style="height: {{ $gridHeight }}px"
                    data-date="{{ $meta['jKey'] }}"
                    data-hour-height="{{ $hourHeight }}"
                >
                    @foreach($hourOffsets as $offset)
                        <div class="absolute left-0 right-0 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]" style="top: {{ $offset }}px"></div>
                    @endforeach

                    @foreach($meta['dayPills'] as $pill)
                        @php
                            if (!isset($pill['top'], $pill['height'], $pill['left_pct'], $pill['width_pct'])) {
                                continue;
                            }
                        @endphp
                        @include('livewire.dashboard.tab.calendar.event-block', ['event' => $pill])
                    @endforeach

                    @if($meta['isToday'])
                        @include('livewire.dashboard.tab.calendar.now-line', ['startIso' => $meta['startIso'], 'spanHours' => $endHour - $startHour])
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>