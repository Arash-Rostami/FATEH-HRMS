<div wire:key="appointment-time-blocks"
     class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
    @if($this->minNoticeHours > 0)
        <div wire:key="reservation-min-notice" class="md:col-span-2 flex items-center gap-1.5 -mb-2">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">schedule</span>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">رزرو حداقل {{ convertToPersian((string) $this->minNoticeHours) }} ساعت قبل از زمان شروع قابل ثبت است</span>
        </div>
    @endif

    @if($this->durationBounds !== null)
        <div wire:key="reservation-duration-bounds" class="md:col-span-2 flex items-center gap-1.5 -mb-2">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">timer</span>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $this->durationBounds }}</span>
        </div>
    @endif

    @php($durArgs = ($this->policies['min_duration_minutes'] ?? 'null') . ', ' . ($this->policies['max_duration_minutes'] ?? 'null'))
    <div class="md:col-span-2 flex items-center gap-1.5 -mb-2">
        <span class="material-symbols-rounded text-[14px]" :class="durationPreview({{ $durArgs }}).valid ? 'text-[var(--md-sys-color-on-surface-variant)]' : 'text-[var(--md-sys-color-error)]'">hourglass_empty</span>
        <span class="text-[11px] font-semibold" :class="durationPreview({{ $durArgs }}).valid ? 'text-[var(--md-sys-color-on-surface-variant)]' : 'text-[var(--md-sys-color-error)]'" x-text="durationPreview({{ $durArgs }}).text"></span>
    </div>

    @php($timePickers = [
            ['type' => 'start', 'icon' => 'hourglass_top', 'label' => 'زمان شروع', 'method' => 'setStartTime', 'activeValue' => $startTime],
            ['type' => 'end', 'icon' => 'hourglass_bottom', 'label' => 'زمان پایان', 'method' => 'setEndTime', 'activeValue' => $endTime],
        ])
    @php($slotMeta = $this->startSlotMeta)
    @php($firstOk = $slotMeta['first'])
    @php($minNoticeArg = $this->minNoticeHours ?? 'null')

    @foreach($timePickers as $picker)
        @php($isStart = $picker['type'] === 'start')
        <div class="min-w-0">
            <label
                class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2 mb-3">
                <span class="material-symbols-rounded text-[16px]">{{ $picker['icon'] }}</span>
                {{ $picker['label'] }}
            </label>
            <div class="relative group w-full">
                <button @click="scrollPrev($el)"
                        class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface)]/90 border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[var(--md-sys-color-on-surface)] opacity-0 group-hover:opacity-100 transition-all active:scale-90 translate-x-1/3">
                    <span class="material-symbols-rounded text-[18px]">chevron_right</span>
                </button>

                <div x-data="{ init() { this.$el.querySelector('[data-first-ok]')?.scrollIntoView({ inline: 'center', block: 'nearest' }) } }"
                     class="flex gap-2 overflow-x-auto pb-2 snap-x scrollbar-hide no-scrollbar w-full"
                     dir="rtl">
                    @foreach($this->availableTimeSlots as $time)
                        @php($isSelected = $picker['activeValue'] === $time ? 'true' : 'false')
                        @php($serverState = $slotMeta['states'][$time] ?? 'ok')
                        <button
                            wire:key="{{ $picker['type'] }}-time-{{ $time }}"
                            wire:click="{{ $picker['method'] }}('{{ $time }}')"
                            @click="{{ $isStart ? 'pickStart' : 'pickEnd' }}('{{ $time }}')"
                            :disabled="slotState('{{ $time }}', {{ $minNoticeArg }}, '{{ $serverState }}') !== 'ok'"
                            {{ ($isStart && $firstOk === $time) ? 'data-first-ok' : '' }}
                            :title="slotTitle(slotState('{{ $time }}', {{ $minNoticeArg }}, '{{ $serverState }}'))"
                            class="shrink-0 px-4 py-2.5 rounded-lg text-sm transition-all duration-300 snap-center border outline-none"
                            :class="slotButtonClass(slotState('{{ $time }}', {{ $minNoticeArg }}, '{{ $serverState }}'), {{ $isSelected }})"
                            :style="slotButtonStyle(slotState('{{ $time }}', {{ $minNoticeArg }}, '{{ $serverState }}'), {{ $isSelected }})"
                        >
                            {{ convertToPersian($time) ?? $time }}
                        </button>
                    @endforeach
                </div>

                <button @click="scrollNext($el)"
                        class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface)]/90 border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[var(--md-sys-color-on-surface)] opacity-0 group-hover:opacity-100 transition-all active:scale-90 -translate-x-1/3">
                    <span class="material-symbols-rounded text-[18px]">chevron_left</span>
                </button>
            </div>
        </div>
    @endforeach
</div>