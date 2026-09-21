<div x-data="settings()" class="relative group"
     data-current-presence="{{ $presence->value }}"
     data-focus-until="{{ $focusUntil ? $focusUntil->getTimestamp() * 1000 : '' }}">
    <button @click="open = !open"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative"
            :class="open ? 'bg-[var(--md-sys-color-surface-container-high)]' : ''">
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">tune</span>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-2 w-64 bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)] animate-slide-down"
         style="display: none;">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/10 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider opacity-60">دسترسی سریع</span>
            <x-ui.modals.close-button close="open = false" size="sm"/>
        </div>

        <div class="p-2 space-y-1">

            {{-- GROUP: خوانایی --}}
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-30 px-3 pt-1 pb-0.5">خوانایی</p>

            {{-- Font Size --}}
            <div class="px-3 py-2">
                <x-dashboard.settings.font-size-control/>
            </div>

            <x-dashboard.settings.toggle-row
                icon="format_ink_highlighter"
                label="خط‌کش خواندن"
                color="violet"
                active="readingRuler"
                action="toggleReadingRuler()"
            />

            <x-dashboard.settings.toggle-row
                icon="content_copy"
                label="کپی خودکار انتخاب"
                color="emerald"
                active="doubleClickCopy"
                action="toggleDoubleClickCopy()"
            />

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            {{-- GROUP: نمایش --}}
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-30 px-3 pt-1 pb-0.5">نمایش</p>

            <x-dashboard.settings.toggle-row
                icon="self_improvement"
                label="حالت تمرکز"
                color="indigo"
                active="$store.focus.active"
                action="toggleFocus()"
            />

            <x-dashboard.settings.background-picker/>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            {{-- Reset --}}
            <button @click="resetApp()"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-rose-500/10 group text-right">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-colors duration-200">
                    <span class="material-symbols-rounded text-[20px]">restart_alt</span>
                </div>
                <span class="text-sm font-medium group-hover:text-rose-500">بازنشانی تنظیمات</span>
            </button>

        </div>
    </div>
</div>
