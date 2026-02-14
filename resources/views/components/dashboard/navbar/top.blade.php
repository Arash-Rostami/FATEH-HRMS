<nav x-cloak x-data="menu()" @resize.window="updatePerPage"
     class="bg-[var(--md-sys-color-primary)] text-white/95 px-4 lg:px-8 flex justify-between items-center h-12 border-b border-white/5 shrink-0 relative z-50">
    <div class="flex items-center gap-3">
        <button
            class="relative w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 flex items-center justify-center">
            <span class="material-symbols-rounded text-[20px]">notifications</span>
            <span
                class="absolute top-1.5 right-1.5 w-2 h-2 bg-[var(--md-sys-color-error)] rounded-full ring-2 ring-[var(--md-sys-color-primary)]"></span>
        </button>
    </div>

    <div class="hidden lg:block absolute left-1/2 -translate-x-1/2">
        <div
            class="flex items-center gap-2 bg-black/10 px-4 py-1.5 rounded-xl border border-white/10 tracking-wider font-semibold text-[13px]">
            <span class="material-symbols-rounded text-[17px] opacity-80">schedule</span>
            01:26 PM
        </div>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden lg:flex items-center gap-2">
            <livewire:dashboard.header.weather />
        </div>

        <div class="hidden lg:block w-px h-6 bg-white/15 mx-1"></div>

        <button @click="toggleMenu" :aria-expanded="menuOpen.toString()" aria-label="Toggle menu"
                class="w-10 h-10 rounded-xl hover:bg-white/10 active:bg-white/15 active:scale-95 transition-all duration-200 flex items-center justify-center"
                :class="menuOpen && 'bg-white/12'">
            <span class="material-symbols-rounded text-[24px]" x-text="menuOpen ? 'close' : 'menu'"></span>
        </button>
    </div>

    <div x-show="menuOpen" @click="closeMenu" x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display: none;"></div>

    <template x-if="menuOpen">
        <div @keydown.window.escape="closeMenu" x-transition:enter="transition-all duration-300 ease-out"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition-all duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-start justify-center pt-2 px-2 pb-0 sm:items-center sm:p-6"
             role="dialog" aria-modal="true">
            <div
                class="w-full h-full sm:h-auto sm:w-[920px] sm:max-w-[95%] bg-[var(--md-sys-color-surface)] rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">

                <div dir="rtl" class="flex flex-col h-full">
                    <div class="relative px-5 py-5 bg-[var(--md-sys-color-primary)] border-b border-white/5 shrink-0">
                        <button @click="closeMenu"
                                class="absolute left-4 top-4 w-10 h-10 flex items-center justify-center rounded-xl bg-white/95 hover:bg-white active:scale-95 shadow-lg hover:shadow-xl transition-all duration-200"
                                aria-label="بستن منو">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface)]">close</span>
                        </button>
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] flex items-center justify-center shadow-md">
                                <span
                                    class="material-symbols-rounded text-[26px] text-[var(--md-sys-color-on-primary-container)]">account_circle</span>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-white mb-0.5">کاربر سیستم</div>
                                <div class="text-sm text-white/80">user@example.com</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="p-4 sm:p-6 pb-24 sm:pb-6 flex flex-col justify-center min-h-full">
                            <div class="w-full max-w-4xl mx-auto">
                                <div class="overflow-hidden rounded-xl">
                                    <div class="flex transition-transform duration-300 ease-out w-full"
                                         :style="`transform: translateX(-${current * 100}%)`">
                                        <template x-for="p in pages" :key="p">
                                            <div class="w-full flex-shrink-0 px-0.5">
                                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-3">
                                                    <template x-for="item in pageItems(p-1)" :key="item.href">
                                                        <a :href="item.href" @click="closeMenu"
                                                           class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] active:scale-[0.96] transition-all duration-200 h-[110px] sm:h-[130px] justify-center border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/20">
                                                            <div
                                                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-gradient-to-tr from-[var(--md-sys-color-primary)]/10 to-[var(--md-sys-color-primary)]/5 border border-[var(--md-sys-color-primary)]/10 shadow-sm group-hover:shadow-md group-hover:scale-110 transition-all duration-300">
                                                                <span
                                                                    class="material-symbols-rounded text-[22px] sm:text-[24px] text-[var(--md-sys-color-primary)]"
                                                                    x-text="item.icon"></span>
                                                            </div>
                                                            <div class="text-center w-full">
                                                                <div
                                                                    class="text-[11px] sm:text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-0.5 truncate px-1"
                                                                    x-text="item.title"></div>
                                                                <div
                                                                    class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:block leading-tight truncate px-1 opacity-80"
                                                                    x-text="item.sub"></div>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-3 sm:gap-4 mt-5 sm:mt-6"
                                     x-show="pages > 1">
                                    <button @click="next" :disabled="current >= pages - 1"
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span
                                            class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_left</span>
                                    </button>

                                    <div class="flex gap-2 sm:gap-2.5">
                                        <template x-for="p in pages" :key="p">
                                            <button @click="current = p-1"
                                                    class="rounded-full transition-all duration-300"
                                                    :class="current === p-1 ? 'w-7 sm:w-8 h-2 sm:h-2.5 bg-[var(--md-sys-color-primary)] shadow-md' : 'w-2 sm:w-2.5 h-2 sm:h-2.5 bg-[var(--md-sys-color-outline-variant)]/40 hover:bg-[var(--md-sys-color-outline-variant)]/70 hover:w-4 sm:hover:w-5'"></button>
                                        </template>
                                    </div>

                                    <button @click="prev" :disabled="current === 0"
                                            class="w-8 h-8 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span
                                            class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_right</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-6 py-3 border-t border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container-lowest)] shrink-0 hidden sm:block">
                        <div
                            class="flex items-center justify-between text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <div class="font-medium">نسخه سیستم: 1.0.0</div>
                            <div
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[12px] font-medium">
                                <livewire:dashboard.header.weather />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</nav>
