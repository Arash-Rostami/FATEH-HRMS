<div x-data="settings()" class="relative group">
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
            <span class="material-symbols-rounded text-[16px] opacity-40 hover:opacity-100 cursor-pointer" @click="open = false">close</span>
        </div>

        <div class="p-2 space-y-1">

            {{-- GROUP: خوانایی --}}
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-30 px-3 pt-1 pb-0.5">خوانایی</p>

            {{-- Font Size --}}
            <div class="px-3 py-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium opacity-70">اندازه فونت</span>
                    <button @click="resetFontSize()"
                            x-show="fontSizeLevel !== 0"
                            x-transition
                            class="text-[9px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors cursor-pointer">
                        بازنشانی
                    </button>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 font-medium"
                          x-show="fontSizeLevel === 0"
                          x-text="getScaleLabel()"></span>
                </div>
                <div class="flex items-center gap-1.5 p-1.5 bg-[var(--md-sys-color-surface-container)]/50 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/5">
                    <button @click="decreaseFontSize()"
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 disabled:opacity-20 disabled:cursor-not-allowed group relative overflow-hidden"
                            :disabled="fontSizeLevel <= minScale"
                            :class="fontSizeLevel <= minScale ? 'bg-transparent' : 'bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 shadow-sm hover:shadow-md'">
                        <span class="material-symbols-rounded text-[20px] transition-transform group-hover:scale-110">remove</span>
                    </button>
                    <div class="flex-1 flex items-center justify-center gap-1.5">
                        <span class="text-[11px] font-bold opacity-30 scale-90 origin-bottom">A</span>
                        <div class="w-px h-4 bg-gradient-to-b from-transparent via-[var(--md-sys-color-outline-variant)]/20 to-transparent"></div>
                        <span class="text-lg font-bold opacity-80" style="transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);"
                              :style="`transform: scale(${1 + (fontSizeLevel * 0.1)})`">A</span>
                    </div>
                    <button @click="increaseFontSize()"
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 disabled:opacity-20 disabled:cursor-not-allowed group relative overflow-hidden"
                            :disabled="fontSizeLevel >= maxScale"
                            :class="fontSizeLevel >= maxScale ? 'bg-transparent' : 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 shadow-sm hover:shadow-md hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-900/50 dark:hover:to-blue-800/50'">
                        <span class="material-symbols-rounded text-[20px] transition-transform group-hover:scale-110">add</span>
                    </button>
                </div>
            </div>

            {{-- Reading Ruler --}}
            <button @click="toggleReadingRuler()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="readingRuler ? 'bg-violet-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">format_ink_highlighter</span>
                    </div>
                    <span class="text-sm font-medium group-hover:text-violet-500">خط‌کش خواندن</span>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="readingRuler ? 'bg-violet-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="readingRuler ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>

            {{-- Auto Copy --}}
            <button @click="toggleDoubleClickCopy()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="doubleClickCopy ? 'bg-emerald-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">content_copy</span>
                    </div>
                    <span class="text-sm font-medium group-hover:text-emerald-500">کپی خودکار انتخاب</span>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="doubleClickCopy ? 'bg-emerald-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="doubleClickCopy ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            {{-- GROUP: نمایش --}}
            <p class="text-[9px] font-bold uppercase tracking-widest opacity-30 px-3 pt-1 pb-0.5">نمایش</p>

            {{-- Focus Mode --}}
            <button @click="toggleFocus()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="focusMode ? 'bg-indigo-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">self_improvement</span>
                    </div>
                    <span class="text-sm font-medium group-hover:text-indigo-500">حالت تمرکز</span>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="focusMode ? 'bg-indigo-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="focusMode ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>

            {{-- Dynamic Background --}}
            <button @click="toggleBackground()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="$store.background.enabled ? 'bg-teal-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">wallpaper</span>
                    </div>
                    <span class="text-sm font-medium group-hover:text-teal-500">پس‌زمینه پویا</span>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="$store.background.enabled ? 'bg-teal-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="$store.background.enabled ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>

            {{-- Pattern (untouched) --}}
            <div class="mb-1">
                <button @click="togglePattern()"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                             :class="$store.background.patternEnabled ? 'bg-amber-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                            <span class="material-symbols-rounded text-[20px]">interests</span>
                        </div>
                        <span class="text-sm font-medium group-hover:text-amber-500">پس‌زمینه طرح دار</span>
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
                     class="mt-2 border-r-2 border-amber-500/30 mr-4 pr-3">
                    <div class="space-y-1 max-h-40 overflow-y-auto overscroll-contain pl-1
                                [&::-webkit-scrollbar]:w-1.5
                                [&::-webkit-scrollbar-track]:bg-transparent
                                [&::-webkit-scrollbar-thumb]:bg-[var(--md-sys-color-outline-variant)]/30
                                [&::-webkit-scrollbar-thumb]:rounded-full
                                hover:[&::-webkit-scrollbar-thumb]:bg-[var(--md-sys-color-outline-variant)]/50
                                [scrollbar-width:thin]">
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
            </div>

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
