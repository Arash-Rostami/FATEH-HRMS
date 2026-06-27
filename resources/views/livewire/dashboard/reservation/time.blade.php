<div wire:key="appointment-time-blocks"
     class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
    @if($this->minNoticeHours > 0)
        <div class="md:col-span-2 flex items-center gap-1.5 -mb-2">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">schedule</span>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">رزرو حداقل {{ convertToPersian((string) $this->minNoticeHours) }} ساعت قبل از زمان شروع قابل ثبت است</span>
        </div>
    @endif

    @if($this->durationBounds !== null)
        <div class="md:col-span-2 flex items-center gap-1.5 -mb-2">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">timer</span>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $this->durationBounds }}</span>
        </div>
    @endif

    @if($this->selectedDuration !== null)
        @php($dur = $this->selectedDuration)
        <div class="md:col-span-2 flex items-center gap-1.5 -mb-2">
            <span class="material-symbols-rounded text-[14px] {{ $dur['valid'] ? 'text-[var(--md-sys-color-on-surface-variant)]' : 'text-[var(--md-sys-color-error)]' }}">hourglass_empty</span>
            <span class="text-[11px] font-semibold {{ $dur['valid'] ? 'text-[var(--md-sys-color-on-surface-variant)]' : 'text-[var(--md-sys-color-error)]' }}">{{ $dur['text'] }}</span>
        </div>
    @endif

    @php($timePickers = [
            ['type' => 'start', 'icon' => 'hourglass_top', 'label' => 'زمان شروع', 'method' => 'setStartTime', 'activeValue' => $startTime],
            ['type' => 'end', 'icon' => 'hourglass_bottom', 'label' => 'زمان پایان', 'method' => 'setEndTime', 'activeValue' => $endTime],
        ])
    @php($slotMeta = $this->startSlotMeta)
    @php($firstOk = $slotMeta['first'])

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
                        @php($state = $slotMeta['states'][$time] ?? 'ok')
                        @php($blocked = $state !== 'ok')
                        <button
                            wire:key="{{ $picker['type'] }}-time-{{ $time }}"
                            wire:click="{{ $picker['method'] }}('{{ $time }}')"
                            {{ $blocked ? 'disabled' : '' }}
                            {{ ($isStart && $firstOk === $time) ? 'data-first-ok' : '' }}
                            title="{{ $blocked ? ($state === 'past' ? 'زمان گذشته' : 'نزدیک به زمان حال — قبل از مهلت اعلام') : '' }}"
                            @class([
                                'shrink-0 px-4 py-2.5 rounded-lg text-sm transition-all duration-300 snap-center border outline-none',
                                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] scale-102 z-10' => $picker['activeValue'] === $time && !$blocked,
                                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] shadow-sm' => $picker['activeValue'] !== $time && !$blocked,
                                'opacity-30 cursor-not-allowed line-through bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40 shadow-none' => $blocked,
                            ])
                            style="{{ $picker['activeValue'] !== $time && !$blocked ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
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