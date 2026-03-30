<div class="flex items-center justify-between mb-3">
    <label
        class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2">
        <span class="material-symbols-rounded text-[16px] font-fill">calendar_month</span>
        تاریخ رزرو
    </label>
</div>

<div class="relative group w-full">
    <button @click="scrollPrev($el)"
            class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-20 w-9 h-9 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface)]/90 backdrop-blur-sm border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[var(--md-sys-color-on-surface)] opacity-0 group-hover:opacity-100 transition-all active:scale-90 translate-x-1/3">
        <span class="material-symbols-rounded text-[20px]">chevron_right</span>
    </button>

    <div
        class="flex gap-3 overflow-x-auto pb-4 pt-1 px-1 -mx-1 snap-x scrollbar-hide no-scrollbar w-full"
        dir="rtl">
        @foreach($this->availableDates as $dt)
            <button
                wire:key="date-{{ $dt['value'] }}"
                wire:click="setDate('{{ $dt['value'] }}')"
                @class([
                        'relative shrink-0 w-[85px] h-[105px] flex flex-col items-center justify-center gap-1 rounded-2xl border transition-all duration-300 snap-center outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--md-sys-color-primary)]',
                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] scale-102 relative top-2 z-10' => $date === $dt['value'],
                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] shadow-sm' => $date !== $dt['value'],
                    ])
                style="{{ $date !== $dt['value'] ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
            >
                <span class="text-[11px] font-medium opacity-80">{{ $dt['day'] }}</span>
                <span
                    class="text-3xl font-black tracking-tighter">{{ convertToPersian($dt['date']) ?? $dt['date'] }}</span>
                <span class="text-xs font-bold">{{ $dt['month'] }}</span>

                @if($dt['isToday'])
                    <div
                        class="absolute top-2 right-2 w-2 h-2 rounded-full {{ $date === $dt['value'] ? 'bg-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-primary)]' }} animate-pulse"></div>
                @endif
            </button>
        @endforeach
    </div>

    <button @click="scrollNext($el)"
            class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-20 w-9 h-9 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface)]/90 backdrop-blur-sm border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[var(--md-sys-color-on-surface)] opacity-0 group-hover:opacity-100 transition-all active:scale-90 -translate-x-1/3">
        <span class="material-symbols-rounded text-[20px]">chevron_left</span>
    </button>
</div>
