    <div
        wire:key="reservation-avantgarde-time-rail-{{ $this->activeTab }}-{{ $this->open ?? 'none' }}-{{ $this->date }}"
        class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-4"
        x-data="reservationTimeRail(@js($timeRail['slots']), @js($timeRail['busyIdx']), {{ $timeRail['nowIdx'] ?? 'null' }}, {{ $timeRail['startIdx'] }}, {{ $timeRail['endIdx'] }})"
        x-on:mousemove.window="onMove"
        x-on:touchmove.window="onMove"
        x-on:mouseup.window="onUp"
        x-on:touchend.window="onUp"
    >
        <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2 mb-4">
            <span class="material-symbols-rounded text-[16px]">schedule</span>
            بازه زمانی
        </label>

        <div class="relative h-10 mx-2 select-none touch-none cursor-pointer" x-ref="rail"
            x-on:mousedown.prevent="trackDown"
            x-on:touchstart.prevent="trackDown"
        >
            <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                @foreach($timeRail['busyRanges'] as $range)
                    <div wire:key="reservation-time-rail-busy-{{ $loop->index }}" class="absolute inset-y-0" style="right: {{ $range['from'] }}%; width: {{ max(0, $range['to'] - $range['from']) }}%; background: repeating-linear-gradient(45deg, color-mix(in srgb, var(--md-sys-color-on-surface-variant) 35%, transparent) 0 4px, transparent 4px 8px);"></div>
                @endforeach

                <div class="absolute inset-y-0 rounded-full" :class="conflict() ? 'bg-[var(--md-sys-color-error)]/50' : 'bg-[var(--md-sys-color-primary)]/75'" :style="`right: ${pct(startIdx)}%; width: ${pct(endIdx) - pct(startIdx)}%;`"></div>
            </div>

            @if($timeRail['nowIdx'] !== null)
                <div wire:key="reservation-time-rail-now" class="absolute top-1/2 -translate-y-1/2 w-0.5 h-5 bg-[var(--md-sys-color-primary)] pointer-events-none" :style="`right: ${pct(nowIdx)}%;`"></div>
            @endif

            <div
                class="absolute top-1/2 w-6 h-6 rounded-sm bg-[var(--md-sys-color-primary)] border-2 border-[var(--md-sys-color-surface)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] -translate-y-1/2 translate-x-1/2 cursor-grab active:cursor-grabbing focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/60"
                :class="dragging === 'start' ? 'scale-110' : ''"
                :style="`right: ${pct(startIdx)}%;`"
                role="slider" aria-label="ساعت شروع" tabindex="0"
                :aria-valuemin="0" :aria-valuemax="slots.length - 1" :aria-valuenow="startIdx" :aria-valuetext="slots[startIdx] ?? ''"
                x-on:mousedown.stop.prevent="onDown('start')"
                x-on:touchstart.stop.prevent="onDown('start')"
                x-on:keydown.arrow-left.prevent="nudge('start', 1)"
                x-on:keydown.arrow-right.prevent="nudge('start', -1)"
            ></div>
            <div
                class="absolute top-1/2 w-6 h-6 rounded-sm bg-[var(--md-sys-color-primary)] border-2 border-[var(--md-sys-color-surface)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] -translate-y-1/2 translate-x-1/2 cursor-grab active:cursor-grabbing focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/60"
                :class="dragging === 'end' ? 'scale-110' : ''"
                :style="`right: ${pct(endIdx)}%;`"
                role="slider" aria-label="ساعت پایان" tabindex="0"
                :aria-valuemin="0" :aria-valuemax="slots.length - 1" :aria-valuenow="endIdx" :aria-valuetext="slots[endIdx] ?? ''"
                x-on:mousedown.stop.prevent="onDown('end')"
                x-on:touchstart.stop.prevent="onDown('end')"
                x-on:keydown.arrow-left.prevent="nudge('end', 1)"
                x-on:keydown.arrow-right.prevent="nudge('end', -1)"
            ></div>
        </div>

        <div class="relative h-4 mt-2 mx-2 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] opacity-60">
            @foreach($timeRail['ticks'] as $tick)
                <span wire:key="reservation-time-rail-tick-{{ $loop->index }}" class="absolute top-0 -translate-x-1/2 whitespace-nowrap" style="right: {{ $tick['pct'] }}%;">{{ convertToPersian($tick['slot']) }}</span>
            @endforeach
        </div>

        <div class="flex justify-between items-center gap-2 mt-3 text-[13px] font-black">
            <span class="px-3 py-1.5 rounded-[14px] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]" x-text="fa(slots[startIdx] ?? '')"></span>
            <span class="flex-1 text-center min-w-0 px-3 py-1.5 rounded-[14px] text-[11px] font-bold truncate ring-1"
                :class="conflict() ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] ring-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)]' : 'bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]'"
                x-text="conflict() ? 'تداخل با رزرو فعلی' : 'مدت: ' + duration()"></span>
            <span class="px-3 py-1.5 rounded-[14px] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]" x-text="fa(slots[endIdx] ?? '')"></span>
        </div>
    </div>