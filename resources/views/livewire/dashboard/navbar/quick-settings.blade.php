<div x-data="quickSettings" class="relative">
    <button @click="open = !open"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative"
            :class="focusMode ? 'bg-indigo-500/20 text-indigo-400 animate-pulse' : 'opacity-70'">
        <span class="material-symbols-rounded text-[22px]" x-text="focusMode ? 'self_improvement' : 'tune'"></span>
    </button>

    <div x-show="open"
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="absolute top-full mt-2 w-64 right-0 p-2 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/10 shadow-2xl z-50 text-[var(--md-sys-color-on-surface)]"
         style="display: none;">

        <div class="text-[10px] uppercase tracking-wider opacity-40 font-bold px-3 py-2 mb-1 text-right">تنظیمات سریع</div>

        <button @click="toggleFocus()"
                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group mb-1">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                     :class="focusMode ? 'bg-indigo-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                    <span class="material-symbols-rounded text-[20px]">self_improvement</span>
                </div>
                <div class="flex flex-col items-start text-right">
                    <span class="text-sm font-medium group-hover:text-indigo-500">حالت تمرکز</span>
                    <span class="text-[10px] opacity-50 group-hover:opacity-70">تغییر وضعیت به مشغول + تمام صفحه</span>
                </div>
            </div>
            <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                 :class="focusMode ? 'bg-indigo-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                     :class="focusMode ? 'translate-x-4' : 'translate-x-0'"></div>
            </div>
        </button>

        <button @click="toggleBackground()"
                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group mb-1">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                     :class="backgroundEnabled ? 'bg-teal-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                    <span class="material-symbols-rounded text-[20px]">wallpaper</span>
                </div>
                <div class="flex flex-col items-start text-right">
                    <span class="text-sm font-medium group-hover:text-teal-500">پس‌زمینه پویا</span>
                    <span class="text-[10px] opacity-50 group-hover:opacity-70">نمایش افکت‌های بصری</span>
                </div>
            </div>
            <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                 :class="backgroundEnabled ? 'bg-teal-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                     :class="backgroundEnabled ? 'translate-x-4' : 'translate-x-0'"></div>
            </div>
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

        <button @click="resetApp()"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-rose-500/10 group text-right">
            <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-colors duration-200">
                <span class="material-symbols-rounded text-[20px]">restart_alt</span>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-medium group-hover:text-rose-500">بازنشانی تنظیمات</span>
                <span class="text-[10px] opacity-50 group-hover:text-rose-400/70">پاکسازی کش و تنظیمات محلی</span>
            </div>
        </button>
    </div>
</div>
