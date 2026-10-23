<div x-show="daysOpen" x-transition.opacity.scale.origin.top.duration.150ms x-on:keydown.escape.window="daysOpen = false" style="display:none;" class="absolute left-0 right-10 z-40 max-md:right-[-2rem]">
    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] overflow-hidden">
        <div class="flex items-center gap-2 px-3 h-10 text-[12px] font-bold text-[var(--md-sys-color-on-surface)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)]">
            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">calendar_month</span>
            <span class="flex-1">{{ $title }}</span>
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]" x-show="dayList.length" x-text="dayList.length"></span>
            @include('livewire.dashboard.messaging.latest-button', ['before' => 'daysOpen = false; ', 'position' => 'relative'])
        </div>
        <div class="max-h-72 overflow-y-auto contact-scrollbar border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
            <template x-for="r in dayRows" :key="r.day">
                <div>
                    <template x-if="r.header">
                        <div class="sticky top-0 z-10 px-3 py-1 text-[10px] font-bold tracking-wide bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_92%,transparent)] text-[var(--md-sys-color-on-surface-variant)]" x-text="r.label"></div>
                    </template>
                    <template x-if="!r.header">
                        <button type="button" x-on:click="jumpToDay(r)"
                                class="group w-full flex items-center gap-2 text-right px-3 py-2 text-[12px] text-[var(--md-sys-color-on-surface)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] cursor-pointer transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="r.today ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]'"></span>
                            <span class="flex-1" :class="r.today ? 'font-bold text-[var(--md-sys-color-primary)]' : ''" x-text="r.label"></span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]" x-text="r.weekday"></span>
                            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)] opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all">arrow_back</span>
                        </button>
                    </template>
                </div>
            </template>
            <div x-show="daysLoading" class="px-3 py-3 text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]">در حال بارگذاری روزهای قدیمی‌تر...</div>
            <div x-show="!dayList.length && !daysLoading" class="px-3 py-5 text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $empty }}</div>
        </div>
    </div>
</div>
