<div x-data="settings()" class="relative group">
    <button @click="open = !open"
            class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative"
            :class="open ? 'bg-[var(--md-sys-color-surface-container-high)]' : ''">
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">tune</span>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-2 w-64 bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)] animate-slide-down"
         style="display: none;">

        <div class="px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/10 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider opacity-60">دسترسی سریع</span>
            <span class="material-symbols-rounded text-[16px] opacity-40 hover:opacity-100 cursor-pointer" @click="open = false">close</span>
        </div>

        <div class="p-2 space-y-1">
            <button @click="toggleFocus()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group mb-1">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="focusMode ? 'bg-indigo-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">self_improvement</span>
                    </div>
                    <div class="flex flex-col items-start text-right">
                        <span class="text-sm font-medium group-hover:text-indigo-500">حالت تمرکز</span>
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
                         :class="$store.background.enabled ? 'bg-teal-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">wallpaper</span>
                    </div>
                    <div class="flex flex-col items-start text-right">
                        <span class="text-sm font-medium group-hover:text-teal-500">پس‌زمینه پویا</span>
                    </div>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="$store.background.enabled ? 'bg-teal-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="$store.background.enabled ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>

            <div class="mb-1">
                <button @click="togglePattern()"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                             :class="$store.background.patternEnabled ? 'bg-amber-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                            <span class="material-symbols-rounded text-[20px]">interests</span>
                        </div>
                        <div class="flex flex-col items-start text-right">
                            <span class="text-sm font-medium group-hover:text-amber-500">پس‌زمینه طرح دار</span>
                        </div>
                    </div>
                    <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                         :class="$store.background.patternEnabled ? 'bg-amber-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                        <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                             :class="$store.background.patternEnabled ? 'translate-x-4' : 'translate-x-0'"></div>
                    </div>
                </button>

                <div x-show="$store.background.patternEnabled"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-2 border-r-2 border-amber-500/30 mr-4 pr-3 space-y-1">
                    <template x-for="pattern in availablePatterns" :key="pattern.id">
                        <div @click="setPattern(pattern.id)"
                             class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]"
                             :class="$store.background.activePattern === pattern.id ? 'bg-[var(--md-sys-color-surface-container-highest)]' : ''">
                            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)]" x-text="pattern.name"></span>
                            <div class="relative w-4 h-4 rounded-full border-2 transition-colors flex items-center justify-center"
                                 :class="$store.background.activePattern === pattern.id ? 'border-amber-500' : 'border-[var(--md-sys-color-outline-variant)]'">
                                <div class="w-2 h-2 rounded-full transition-transform duration-200"
                                     :class="$store.background.activePattern === pattern.id ? 'bg-amber-500 scale-100' : 'bg-transparent scale-0'">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            <button @click="resetApp()"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-rose-500/10 group text-right">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-colors duration-200">
                    <span class="material-symbols-rounded text-[20px]">restart_alt</span>
                </div>
                <div class="flex flex-col items-start">
                    <span class="text-sm font-medium group-hover:text-rose-500">بازنشانی تنظیمات</span>
                </div>
            </button>
        </div>
    </div>
</div>
